<?php

declare(strict_types=1);

namespace App\Domain\Strava\ImportStravaData;

use App\Domain\Activity\BestEffort\CalculateBestActivityEfforts\CalculateBestActivityEfforts;
use App\Domain\Activity\ImportActivities\ImportActivities;
use App\Domain\Activity\Lap\ImportActivityLaps\ImportActivityLaps;
use App\Domain\Activity\Split\ImportActivitySplits\ImportActivitySplits;
use App\Domain\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Activity\Stream\CalculateNormalizedPower\CalculateNormalizedPower;
use App\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams\CalculateCombinedStreams;
use App\Domain\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Athlete\ImportAthlete\ImportAthlete;
use App\Domain\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Gear\CustomGear\LinkCustomGearToActivities\LinkCustomGearToActivities;
use App\Domain\Gear\ImportGear\ImportGear;
use App\Domain\Segment\ImportSegments\ImportSegments;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\FileSystem\PermissionChecker;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;

final readonly class ImportStravaDataCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private CommandBus $commandBus,
        private MigrationRunner $migrationRunner,
        private PermissionChecker $fileSystemPermissionChecker,
        private Connection $connection,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportStravaData);

        $output = $command->getOutput();
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return;
        }
        $consoleApplication = $command->getConsoleApplication();
        $output->writeln('Running database migrations...');

        $this->migrationRunner->run(
            application: $consoleApplication,
            output: $output
        );

        $this->commandBus->dispatch(new ImportAthlete($output));
        $this->commandBus->dispatch(new ImportGear($output));
        $this->commandBus->dispatch(new ImportActivities($output));
        $this->commandBus->dispatch(new LinkCustomGearToActivities($output));
        $this->commandBus->dispatch(new ImportActivitySplits($output));
        $this->commandBus->dispatch(new ImportActivityLaps($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output));
        $this->commandBus->dispatch(new CalculateBestActivityEfforts($output));
        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));
        $this->commandBus->dispatch(new CalculateNormalizedPower($output));
        $this->commandBus->dispatch(new CalculateCombinedStreams($output));

        if ($rateLimits = $this->strava->getRateLimit()) {
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
