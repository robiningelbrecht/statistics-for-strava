<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppUrl;
use App\Console\Daemon\RunFileImportAndBuildAppConsoleCommand;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Import\WatchDirectory;
use App\Infrastructure\Exception\EntityNotFound;
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
use App\Tests\Infrastructure\Doctrine\Migrations\VoidMigrationRunner;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;
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

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->watchStorage = $this->getContainer()->get('default.storage');
        $this->watchStorage->deleteDirectory('watch');
        $this->keyValueStore = $this->getContainer()->get(KeyValueStore::class);

        $this->command = new RunFileImportAndBuildAppConsoleCommand(
            commandBus: $this->commandBus = new SpyCommandBus(),
            activityIdRepository: $this->getContainer()->get(ActivityIdRepository::class),
            watchDirectory: $this->getContainer()->get(WatchDirectory::class),
            resourceUsage: new FixedResourceUsage(),
            migrationRunner: new VoidMigrationRunner(),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            appUrl: AppUrl::fromString('http://localhost'),
            clock: PausedClock::fromString(self::TODAY),
            keyValueStore: $this->keyValueStore,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
