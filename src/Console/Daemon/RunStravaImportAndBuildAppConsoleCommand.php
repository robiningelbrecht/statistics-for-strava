<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\Application\AppUrl;
use App\Application\Build\RunBuild\RunBuild;
use App\Application\Import\CalculateActivityMetrics\CalculateActivityMetrics;
use App\Application\Import\StravaImport\DeleteActivitiesMarkedForDeletion\DeleteActivitiesMarkedForDeletion;
use App\Application\Import\StravaImport\ImportActivities\ImportActivities;
use App\Application\Import\StravaImport\ImportAthlete\ImportAthlete;
use App\Application\Import\StravaImport\ImportChallenges\ImportChallenges;
use App\Application\Import\StravaImport\ImportGear\ImportGear;
use App\Application\Import\StravaImport\ImportSegments\ImportSegments;
use App\Application\Import\StravaImport\LinkCustomGearToActivities\LinkCustomGearToActivities;
use App\Application\Import\StravaImport\ProcessRawActivityData\ProcessRawActivityData;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Domain\Strava\RateLimit\StravaRateLimits;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\FileSystem\PermissionChecker;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Mutex\LockIsAlreadyAcquired;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
#[AsCommand(name: RunStravaImportAndBuildAppConsoleCommand::NAME, description: 'Run strava import')]
final class RunStravaImportAndBuildAppConsoleCommand extends Command
{
    public const string NAME = 'app:cron:run-strava-import';
    public const string SKIP_IMPORT_OPTION = 'skipImport';
    public const string SKIP_BUILD_OPTION = 'skipBuild';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ResourceUsage $resourceUsage,
        private readonly Strava $strava,
        private readonly LoggerInterface $logger,
        private readonly Mutex $mutex,
        private readonly MigrationRunner $migrationRunner,
        private readonly PermissionChecker $fileSystemPermissionChecker,
        private readonly Connection $connection,
        private readonly AppUrl $appUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('restrictToActivityId', InputArgument::OPTIONAL);
        $this->addOption(self::SKIP_IMPORT_OPTION, null, InputOption::VALUE_NONE);
        $this->addOption(self::SKIP_BUILD_OPTION, null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));
        $this->resourceUsage->startTimer();

        $restrictToActivityIds = null;
        if ($restrictToActivityId = $input->getArgument('restrictToActivityId')) {
            $restrictToActivityIds = ActivityIds::fromArray([ActivityId::fromUnprefixed($restrictToActivityId)]);
        }

        $this->migrationRunner->run($output);

        try {
            $this->mutex->acquireLock('runStravaImportAndBuildApp');
        } catch (LockIsAlreadyAcquired) {
            // Another process is importing data, postpone import.
            $output->writeln('<comment>Postponing Strava import, another process is importing data.</comment>');

            return Command::SUCCESS;
        }

        try {
            if (!$input->getOption(self::SKIP_IMPORT_OPTION)) {
                try {
                    $this->fileSystemPermissionChecker->ensureWriteAccess();
                } catch (UnableToWriteFile|UnableToCreateDirectory) {
                    $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

                    return Command::SUCCESS;
                }

                $this->commandBus->dispatch(new ImportAthlete($output));
                $this->commandBus->dispatch(new ImportActivities(
                    output: $output,
                    restrictToActivityIds: $restrictToActivityIds
                ));
                $this->commandBus->dispatch(new ImportGear(
                    output: $output,
                    restrictToActivityIds: $restrictToActivityIds
                ));
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
            if (!$input->getOption(self::SKIP_BUILD_OPTION)) {
                $this->commandBus->dispatch(new RunBuild(
                    output: $output,
                ));
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        $this->resourceUsage->stopTimer();
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New import and build of your stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1'],
            actionUrl: $this->appUrl
        ));

        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
