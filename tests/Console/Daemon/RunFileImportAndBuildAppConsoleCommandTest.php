<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppUrl;
use App\Console\Daemon\RunFileImportAndBuildAppConsoleCommand;
use App\Domain\Import\WatchDirectory;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
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
        $command = $this->getCommandInApplication('app:cron:run-file-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertSame(self::TODAY, (string) $this->keyValueStore->find(Key::APP_LAST_BUILT_ON));
    }

    public function testRunsWhenLastBuiltOnAPreviousDay(): void
    {
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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->watchStorage = $this->getContainer()->get('default.storage');
        $this->watchStorage->deleteDirectory('watch');
        $this->keyValueStore = $this->getContainer()->get(KeyValueStore::class);

        $this->command = new RunFileImportAndBuildAppConsoleCommand(
            $this->commandBus = new SpyCommandBus(),
            $this->getContainer()->get(WatchDirectory::class),
            new FixedResourceUsage(),
            new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            AppUrl::fromString('http://localhost'),
            PausedClock::fromString(self::TODAY),
            $this->keyValueStore,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
