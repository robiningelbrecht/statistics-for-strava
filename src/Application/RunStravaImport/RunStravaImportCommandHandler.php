<?php

declare(strict_types=1);

namespace App\Application\RunStravaImport;

use App\Application\Import\CalculateActivityMetrics\CalculateActivityMetrics;
use App\Application\Import\StravaImport\DeleteActivitiesMarkedForDeletion\DeleteActivitiesMarkedForDeletion;
use App\Application\Import\StravaImport\ImportActivities\ImportActivities;
use App\Application\Import\StravaImport\ImportAthlete\ImportAthlete;
use App\Application\Import\StravaImport\ImportChallenges\ImportChallenges;
use App\Application\Import\StravaImport\ImportGear\ImportGear;
use App\Application\Import\StravaImport\ImportSegments\ImportSegments;
use App\Application\Import\StravaImport\LinkCustomGearToActivities\LinkCustomGearToActivities;
use App\Application\Import\StravaImport\ProcessRawActivityData\ProcessRawActivityData;
use App\Domain\Strava\RateLimit\StravaRateLimits;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\FileSystem\PermissionChecker;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;

final readonly class RunStravaImportCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private CommandBus $commandBus,
        private PermissionChecker $fileSystemPermissionChecker,
        private Connection $connection,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof RunStravaImport);

        $output = $command->getOutput();
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return;
        }

        $this->commandBus->dispatch(new ImportAthlete($output));
        $this->commandBus->dispatch(new ImportActivities(
            output: $output,
            restrictToActivityIds: $command->getRestrictToActivityIds()
        ));
        $this->commandBus->dispatch(new ImportGear(
            output: $output,
            restrictToActivityIds: $command->getRestrictToActivityIds())
        );
        $this->commandBus->dispatch(new ProcessRawActivityData($output));
        $this->commandBus->dispatch(new LinkCustomGearToActivities($output));
        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        $this->commandBus->dispatch(new CalculateActivityMetrics($output));
        $this->commandBus->dispatch(new DeleteActivitiesMarkedForDeletion($output));

        if (($rateLimits = $this->strava->getRateLimit()) instanceof StravaRateLimits) {
            $output->title('STRAVA API RATE LIMITS');
            $output->listing([
                sprintf('15 min rate: %s/%s', $rateLimits->getFifteenMinRateUsage(), $rateLimits->getFifteenMinRateLimit()),
                sprintf('15 min read rate: %s/%s', $rateLimits->getFifteenMinReadRateUsage(), $rateLimits->getFifteenMinReadRateLimit()),
                sprintf('daily rate: %s/%s', $rateLimits->getDailyRateUsage(), $rateLimits->getDailyRateLimit()),
                sprintf('daily read rate: %s/%s', $rateLimits->getDailyReadRateUsage(), $rateLimits->getDailyReadRateLimit()),
            ]);
        }

        $this->connection->executeStatement('VACUUM');
        $output->writeln('Database got vacuumed 🧹');
    }
}
