<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppStatusChecker;
use App\Application\AppUrl;
use App\Console\Daemon\RunFileImportAndBuildAppConsoleCommand;
use App\Console\Daemon\RunStravaImportAndBuildAppConsoleCommand;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Import\ImportMode;
use App\Domain\Import\WatchDirectory;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\FileSystem\PermissionChecker;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\FileSystem\UnwritablePermissionChecker;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunFileImportAndBuildAppConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private const string TODAY = '2025-12-04';

    private RunFileImportAndBuildAppConsoleCommand $command;
    private SpyCommandBus $commandBus;
    private FilesystemOperator $watchStorage;
    private KeyValueStore $keyValueStore;

    public function testRunsAndRecordsDateWhenNotYetBuiltToday(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertSame(self::TODAY, (string) $this->keyValueStore->find(Key::APP_LAST_BUILT_ON));
    }

    public function testRunsWhenLastBuiltOnAPreviousDay(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));

        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::APP_LAST_BUILT_ON,
            value: Value::fromString('2025-12-03'),
        ));

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertSame(self::TODAY, (string) $this->keyValueStore->find(Key::APP_LAST_BUILT_ON));
    }

    public function testSkipsWhenAlreadyBuiltTodayAndNoFiles(): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::APP_LAST_BUILT_ON,
            value: Value::fromString(self::TODAY),
        ));

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
        $this->assertStringContainsString('No files left to process...', $commandTester->getDisplay());
    }

    public function testRunsWhenFilesArePresentEvenIfAlreadyBuiltToday(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));

        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::APP_LAST_BUILT_ON,
            value: Value::fromString(self::TODAY),
        ));
        $this->watchStorage->write('watch/ride.fit', 'raw-fit-bytes');

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testDoesNotBuildWhenNoActivitiesHaveBeenImported(): void
    {
        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertStringContainsString(
            'Wait until at least one activity has been imported before building the app',
            $commandTester->getDisplay(),
        );

        $this->expectException(EntityNotFound::class);
        $this->keyValueStore->find(Key::APP_LAST_BUILT_ON);
    }

    public function testPostponesWhenLockIsAlreadyAcquired(): void
    {
        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test", "heartbeat": 1764806400}']
        );

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
        $this->assertStringContainsString(
            'Postponing file import, another process is importing data.',
            $commandTester->getDisplay(),
        );
    }

    public function testImportsButDoesNotBuildWhenSkipBuildOptionIsSet(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));
        $this->watchStorage->write('watch/ride.fit', 'raw-fit-bytes');

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--'.RunStravaImportAndBuildAppConsoleCommand::SKIP_BUILD_OPTION => true,
        ]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));

        $this->expectException(EntityNotFound::class);
        $this->keyValueStore->find(Key::APP_LAST_BUILT_ON);
    }

    public function testBuildsButDoesNotImportWhenSkipImportOptionIsSet(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));

        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--'.RunStravaImportAndBuildAppConsoleCommand::SKIP_IMPORT_OPTION => true,
        ]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertSame(self::TODAY, (string) $this->keyValueStore->find(Key::APP_LAST_BUILT_ON));
    }

    public function testReturnsEarlyWhenImportModeIsStrava(): void
    {
        $command = $this->buildCommand(new SpyCommandBus(), importMode: ImportMode::STRAVA_API);

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find('app:cron:run-file-import'));
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertStringContainsString('Cannot import files. IMPORT_MODE=stravaApi', $commandTester->getDisplay());
    }

    public function testShowsErrorAndReleasesLockWhenWriteAccessFails(): void
    {
        $this->watchStorage->write('watch/ride.fit', 'raw-fit-bytes');

        $command = $this->buildCommand(
            $commandBus = new SpyCommandBus(),
            permissionChecker: new UnwritablePermissionChecker(),
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find('app:cron:run-file-import'));
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertStringContainsString(
            'Make sure the container has write permissions to "storage/database" and "storage/files" on the host system',
            $commandTester->getDisplay(),
        );
        $this->assertEmpty($commandBus->getDispatchedCommands());

        $row = $this->getConnection()->fetchOne(
            'SELECT `value` FROM KeyValue WHERE `key` = :key',
            ['key' => 'lock.importDataOrBuildApp']
        );
        $this->assertFalse($row, 'Expected the mutex lock to be released');
    }

    public function testLogsReleasesLockAndRethrowsWhenBuildFails(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));

        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->expects($this->once())->method('dispatch')->willThrowException(new \RuntimeException('OH NO ERROR'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('OH NO ERROR');

        $command = $this->buildCommand($commandBus, logger: $logger);

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find('app:cron:run-file-import'));

        $thrown = null;
        try {
            $commandTester->execute(['command' => $command->getName()]);
        } catch (\RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertSame('OH NO ERROR', $thrown?->getMessage());
        $row = $this->getConnection()->fetchOne(
            'SELECT `value` FROM KeyValue WHERE `key` = :key',
            ['key' => 'lock.importDataOrBuildApp']
        );
        $this->assertFalse($row, 'Expected the mutex lock to be released');
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->watchStorage = $this->getContainer()->get('default.storage');
        $this->watchStorage->deleteDirectory('watch');
        $this->keyValueStore = $this->getContainer()->get(KeyValueStore::class);

        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'Robin',
            'lastname' => 'Ingelbrecht',
        ]));

        $this->command = $this->buildCommand($this->commandBus = new SpyCommandBus());
    }

    private function buildCommand(
        CommandBus $commandBus,
        PermissionChecker $permissionChecker = new SuccessfulPermissionChecker(),
        ImportMode $importMode = ImportMode::FILES,
        LoggerInterface $logger = new NullLogger(),
    ): RunFileImportAndBuildAppConsoleCommand {
        $connection = $this->createStub(Connection::class);
        $connection->method('executeStatement')->willReturn(0);

        return new RunFileImportAndBuildAppConsoleCommand(
            commandBus: $commandBus,
            appStatusChecker: new AppStatusChecker(
                $this->getContainer()->get(AthleteRepository::class),
                $this->getContainer()->get(ActivityIdRepository::class),
                $permissionChecker,
            ),
            watchDirectory: $this->getContainer()->get(WatchDirectory::class),
            resourceUsage: new FixedResourceUsage(),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            appUrl: AppUrl::fromString('http://localhost'),
            clock: PausedClock::fromString(self::TODAY),
            keyValueStore: $this->keyValueStore,
            connection: $connection,
            logger: $logger,
            importMode: $importMode,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
