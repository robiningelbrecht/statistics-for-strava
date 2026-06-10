<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\Application\AppUrl;
use App\Application\Build\RunBuild\RunBuild;
use App\Application\Import\RunFileImport\RunFileImport;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Import\WatchDirectory;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Mutex\LockIsAlreadyAcquired;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
#[AsCommand(name: 'app:cron:run-file-import', description: 'Run file import')]
final class RunFileImportAndBuildAppConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ActivityIdRepository $activityIdRepository,
        private readonly WatchDirectory $watchDirectory,
        private readonly ResourceUsage $resourceUsage,
        private readonly MigrationRunner $migrationRunner,
        private readonly Mutex $mutex,
        private readonly AppUrl $appUrl,
        private readonly Clock $clock,
        private readonly KeyValueStore $keyValueStore,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);
        $this->migrationRunner->run($output);

        $today = $this->clock->getCurrentDateTimeImmutable()->format('Y-m-d');
        $watchDirectoryHasFiles = $this->watchDirectory->hasFilesThatCanBeProcessed();

        try {
            $alreadyBuiltToday = $today === (string) $this->keyValueStore->find(Key::APP_LAST_BUILT_ON);
        } catch (EntityNotFound) {
            $alreadyBuiltToday = false;
        }

        if (!$watchDirectoryHasFiles && $alreadyBuiltToday) {
            $output->writeln('No files left to process...');

            return Command::SUCCESS;
        }

        $this->resourceUsage->startTimer();

        try {
            $this->mutex->acquireLock('runFileImportAndBuildApp');
        } catch (LockIsAlreadyAcquired) {
            // Another process is importing data, postpone import.
            $output->writeln('<comment>Postponing file import, another process is importing data.</comment>');

            return Command::SUCCESS;
        }

        if ($watchDirectoryHasFiles) {
            $this->commandBus->dispatch(new RunFileImport(
                output: $output,
            ));
        }

        if ($this->activityIdRepository->count() <= 0) {
            $this->mutex->releaseLock();
            $output->writeln('<error>Wait until at least one activity has been imported before building the app</error>');

            return Command::SUCCESS;
        }

        $this->commandBus->dispatch(new RunBuild(
            output: $output,
        ));

        $this->mutex->releaseLock();

        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::APP_LAST_BUILT_ON,
            value: Value::fromString($today),
        ));

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
