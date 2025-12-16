<?php

namespace App\Application\Import\ImportGear;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Strava\RateLimit\StravaRateLimitHasBeenReached;
use App\Domain\Strava\Strava;
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
        private ActivityRepository $activityRepository,
        private CustomGearConfig $customGearConfig,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportGear);
        $command->getOutput()->writeln('Importing gear...');

        $this->strava->setConsoleOutput($command->getOutput());

        $allStravaGearIdsReferencedOnActivities = $this->activityRepository->findUniqueStravaGearIds(null);

        if ($this->customGearConfig->isFeatureEnabled()) {
            /** @var GearId $customGearId */
            foreach ($this->customGearConfig->getGearIds() as $customGearId) {
                if (!$allStravaGearIdsReferencedOnActivities->has($customGearId)) {
                    continue;
                }

                $command->getOutput()->writeln(sprintf(
                    '<error>Custom gear id "%s" conflicts with Strava gear id, please change the custom gear id.</error>',
                    $customGearId
                ));

                return;
            }
        }

        $stravaGearIdsToImport = $allStravaGearIdsReferencedOnActivities;
        if ($command->isPartialImport()) {
            // We only want to update gears that are referenced on the activities to be imported.
            $stravaGearIdsToImport = $this->activityRepository->findUniqueStravaGearIds($command->getRestrictToActivityIds());
        }

        foreach ($stravaGearIdsToImport as $gearId) {
            try {
                $stravaGear = $this->strava->getGear($gearId);
            } catch (StravaRateLimitHasBeenReached $exception) {
                $command->getOutput()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

                return;
            } catch (ClientException|RequestException $exception) {
                if (!$exception->getResponse()) {
                    // Re-throw, we only want to catch supported error codes.
                    throw $exception;
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
                    type: GearType::IMPORTED,
                    distanceInMeter: Meter::from($stravaGear['distance']),
                    createdOn: $this->clock->getCurrentDateTimeImmutable(),
                    name: $stravaGear['name'],
                    isRetired: $stravaGear['retired'] ?? false
                );
            }
            $this->importedGearRepository->save($gear);
            $command->getOutput()->writeln(sprintf('  => Imported gear "%s"', $gear->getName()));
        }

        if ($this->customGearConfig->isFeatureEnabled()) {
            // Remove all existing custom gears before importing new ones.
            // This is to ensure that if a custom gear is removed from the config, it will also be removed from the database.
            // It's the lazy approach, but it works for now.
            $this->customGearRepository->removeAll();
            $customGearsDefinedInConfig = $this->customGearConfig->getCustomGears();

            foreach ($customGearsDefinedInConfig as $customGear) {
                $this->customGearRepository->save($customGear);
                $command->getOutput()->writeln(sprintf('  => Imported custom gear "%s"', $customGear->getName()));
            }
        }
    }
}
