<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\RunBuild\RunBuild;
use App\Application\RunImport\RunImport;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
final readonly class importDataAndBuildAppCronAction implements RunnableCronAction
{
    use ProvideConsoleIntro;

    public function __construct(
        private CommandBus $commandBus,
        private ResourceUsage $resourceUsage,
        private AppUrl $appUrl,
        private Mutex $mutex,
        private MigrationRunner $migrationRunner,
    ) {
    }

    public function getId(): string
    {
        return 'importDataAndBuildApp';
    }

    public function requiresDatabaseSchemaToBeUpdated(): bool
    {
        return false;
    }

    public function getMutexTtl(): int
    {
        return 1800;
    }

    public function run(SymfonyStyle $output): void
    {
        $this->outputConsoleIntro($output);
        $this->resourceUsage->startTimer();

        if (!$this->migrationRunner->databaseIsInitialized()) {
            // This can occur when the import is run for the very first time
            // and the migrations still need to run for the first time.
            // We need to run the migrations first for the mutex to work.
            $this->migrationRunner->run($output);
        }

        $this->mutex->acquireLock('importDataAndBuildAppCronAction');

        $this->commandBus->dispatch(new RunImport(
            output: $output,
        ));
        $this->commandBus->dispatch(new RunBuild(
            output: $output,
        ));

        $this->resourceUsage->stopTimer();
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New import and build of your Strava stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1'],
            actionUrl: $this->appUrl
        ));
        $this->mutex->releaseLock();

        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));
    }
}
