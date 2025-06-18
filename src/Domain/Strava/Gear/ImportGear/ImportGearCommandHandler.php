<?php

namespace App\Domain\Strava\Gear\ImportGear;

use App\Domain\Strava\Gear\CustomGear\CustomGearConfig;
use App\Domain\Strava\Gear\CustomGear\CustomGearRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\ImportedGear\ImportedGear;
use App\Domain\Strava\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

final readonly class ImportGearCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ImportedGearRepository $importedGearRepository,
        private CustomGearRepository $customGearRepository,
        private CustomGearConfig $customGearConfig,
        private StravaDataImportStatus $stravaDataImportStatus,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportGear);
        $command->getOutput()->writeln('Importing gear...');

        $stravaGearIds = GearIds::fromArray(array_unique(array_filter(array_map(
            fn (array $activity) => GearId::fromOptionalUnprefixed($activity['gear_id']),
            $this->strava->getActivities(),
        ))));

        if ($this->customGearConfig->isFeatureEnabled()) {
            /** @var GearId $customGearId */
            foreach ($this->customGearConfig->getGearIds() as $customGearId) {
                if (!$stravaGearIds->has($customGearId)) {
                    continue;
                }

                $command->getOutput()->writeln(sprintf(
                    '<error>Custom gear id "%s" conflicts with Strava gear id, please change the custom gear id.</error>',
                    $customGearId
                ));
                $this->stravaDataImportStatus->markGearImportAsUncompleted();

                return;
            }
        }

        foreach ($stravaGearIds as $gearId) {
            try {
                $stravaGear = $this->strava->getGear($gearId);
            } catch (ClientException|RequestException $exception) {
                $this->stravaDataImportStatus->markGearImportAsUncompleted();

                if (!$exception->getResponse()) {
                    // Re-throw, we only want to catch supported error codes.
                    throw $exception;
                }

                if (429 === $exception->getResponse()->getStatusCode()) {
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                    $command->getOutput()->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');

                    return;
                }

                $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));

                return;
            }

            try {
                $gear = $this->importedGearRepository->find($gearId);
                $gear
                    ->updateName($stravaGear['name'])
                    ->updateDistance(Meter::from($stravaGear['distance']))
                    ->updateIsRetired($stravaGear['retired'] ?? false);
            } catch (EntityNotFound) {
                $gear = ImportedGear::create(
                    gearId: $gearId,
                    distanceInMeter: Meter::from($stravaGear['distance']),
                    createdOn: $this->clock->getCurrentDateTimeImmutable(),
                    name: $stravaGear['name'],
                    isRetired: $stravaGear['retired'] ?? false
                );
            }
            $this->importedGearRepository->save($gear);
            $command->getOutput()->writeln(sprintf('  => Imported/updated gear "%s"', $gear->getName()));
        }

        if ($this->customGearConfig->isFeatureEnabled()) {
            // Remove all existing custom gears before importing new ones.
            // This is to ensure that if a custom gear is removed from the config, it will also be removed from the database.
            // It's the lazy approach, but it works for now.
            $this->customGearRepository->removeAll();
            $customGearsDefinedInConfig = $this->customGearConfig->getCustomGears();

            foreach ($customGearsDefinedInConfig as $customGear) {
                $this->customGearRepository->save($customGear);
                $command->getOutput()->writeln(sprintf('  => Imported/updated custom gear "%s"', $customGear->getName()));
            }
        }

        $this->stravaDataImportStatus->markGearImportAsCompleted();
    }
}
