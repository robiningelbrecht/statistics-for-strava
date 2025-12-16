<?php

declare(strict_types=1);

namespace App\Application\RunImport;

use App\Application\Import\CalculateBestActivityEfforts\CalculateBestActivityEfforts;
use App\Application\Import\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Application\Import\CalculateCombinedStreams\CalculateCombinedStreams;
use App\Application\Import\CalculateNormalizedPower\CalculateNormalizedPower;
use App\Application\Import\CalculateStreamValueDistribution\CalculateStreamValueDistribution;
use App\Application\Import\DeleteActivities\DeleteActivities;
use App\Application\Import\ImportActivities\ImportActivities;
use App\Application\Import\ImportActivityLaps\ImportActivityLaps;
use App\Application\Import\ImportActivitySplits\ImportActivitySplits;
use App\Application\Import\ImportActivityStreams\ImportActivityStreams;
use App\Application\Import\ImportAthlete\ImportAthlete;
use App\Application\Import\ImportChallenges\ImportChallenges;
use App\Application\Import\ImportGear\ImportGear;
use App\Application\Import\ImportSegments\ImportSegments;
use App\Application\Import\LinkCustomGearToActivities\LinkCustomGearToActivities;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\FileSystem\PermissionChecker;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;

final readonly class RunImportCommandHandler implements CommandHandler
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
        assert($command instanceof RunImport);

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
        $this->commandBus->dispatch(new LinkCustomGearToActivities($output));
        $this->commandBus->dispatch(new ImportActivitySplits($output));
        $this->commandBus->dispatch(new ImportActivityLaps($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output));
        $this->commandBus->dispatch(new CalculateBestActivityEfforts($output));
        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));
        $this->commandBus->dispatch(new CalculateStreamValueDistribution($output));
        $this->commandBus->dispatch(new CalculateNormalizedPower($output));
        $this->commandBus->dispatch(new CalculateCombinedStreams($output));
        $this->commandBus->dispatch(new DeleteActivities($output));

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
