<?php

namespace App\Console;

use App\BuildApp\AppVersion;
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
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\FileSystem\PermissionChecker;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    public function __construct(
        private readonly Strava $strava,
        private readonly CommandBus $commandBus,
        private readonly PermissionChecker $fileSystemPermissionChecker,
        private readonly MigrationRunner $migrationRunner,
        private readonly ResourceUsage $resourceUsage,
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (is_null($consoleApplication = $this->getApplication())) {
            throw new \RuntimeException('Console application is not set.');
        }
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));

        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return Command::FAILURE;
        }

        $this->resourceUsage->startTimer();

        $output->block(
            messages: sprintf('Statistics for Strava %s', AppVersion::getSemanticVersion()),
            style: 'fg=black;bg=green',
            padding: true
        );

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

        $rateLimits = $this->strava->getRateLimit();

        $output->title('STRAVA API RATE LIMITS');
        $output->listing($rateLimits);

        $this->connection->executeStatement('VACUUM');
        $output->writeln('Database got vacuumed 🧹');

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
