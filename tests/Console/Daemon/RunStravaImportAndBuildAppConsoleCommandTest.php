<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppStatusChecker;
use App\Application\AppUrl;
use App\Console\Daemon\RunStravaImportAndBuildAppConsoleCommand;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Import\ImportMode;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunStravaImportAndBuildAppConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private const string TODAY = '2025-12-04';

    private RunStravaImportAndBuildAppConsoleCommand $command;
    private SpyCommandBus $commandBus;

    public function testRun(): void
    {
        $command = $this->getCommandInApplication('app:cron:run-strava-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testRunWithRestrictToActivityIds(): void
    {
        $command = $this->getCommandInApplication('app:cron:run-strava-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            RunStravaImportAndBuildAppConsoleCommand::RESTRICT_TO_ACTIVITY_IDS_ARGUMENT => 'activity-1,activity-2',
        ]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testImportsButDoesNotBuildWhenSkipBuildOptionIsSet(): void
    {
        $command = $this->getCommandInApplication('app:cron:run-strava-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--'.RunStravaImportAndBuildAppConsoleCommand::SKIP_BUILD_OPTION => true,
        ]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testBuildsButDoesNotImportWhenSkipImportOptionIsSet(): void
    {
        $command = $this->getCommandInApplication('app:cron:run-strava-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--'.RunStravaImportAndBuildAppConsoleCommand::SKIP_IMPORT_OPTION => true,
        ]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testReturnsEarlyInFileMode(): void
    {
        $command = $this->buildCommand(
            commandBus: $this->commandBus,
            importMode: ImportMode::FILES,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find('app:cron:run-strava-import'));
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
        $this->assertStringContainsString('Cannot import files. IMPORT_MODE=files', $commandTester->getDisplay());
    }

    public function testPostponesWhenLockIsAlreadyAcquired(): void
    {
        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test", "heartbeat": 1764806400}']
        );

        $command = $this->getCommandInApplication('app:cron:run-strava-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
        $this->assertStringContainsString(
            'Postponing Strava import, another process is importing data.',
            $commandTester->getDisplay(),
        );
    }

    public function testLogsAndRethrowsWhenImportFails(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('OH NO ERROR'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('OH NO ERROR');

        $command = $this->buildCommand(
            commandBus: $commandBus,
            logger: $logger,
        );

        $this->expectExceptionObject(new \RuntimeException('OH NO ERROR'));

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($application->find('app:cron:run-strava-import'));
        $commandTester->execute(['command' => $command->getName()]);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'Robin',
            'lastname' => 'Ingelbrecht',
        ]));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            [],
        ));

        $this->command = $this->buildCommand(commandBus: $this->commandBus = new SpyCommandBus());
    }

    private function buildCommand(
        CommandBus $commandBus,
        ImportMode $importMode = ImportMode::STRAVA_API,
        ?LoggerInterface $logger = null,
    ): RunStravaImportAndBuildAppConsoleCommand {
        $connection = $this->createStub(Connection::class);
        $connection->method('executeStatement')->willReturn(0);

        return new RunStravaImportAndBuildAppConsoleCommand(
            commandBus: $commandBus,
            resourceUsage: new FixedResourceUsage(),
            strava: $this->getContainer()->get(Strava::class),
            logger: $logger ?? new NullLogger(),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            appStatusChecker: new AppStatusChecker(
                $this->getContainer()->get(AthleteRepository::class),
                $this->getContainer()->get(ActivityIdRepository::class),
                new SuccessfulPermissionChecker(),
            ),
            connection: $connection,
            appUrl: AppUrl::fromString('http://localhost'),
            importMode: $importMode,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
