<?php

namespace App\Tests\Console;

use App\Console\ImportStravaDataConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportStravaDataConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private ImportStravaDataConsoleCommand $importStravaDataConsoleCommand;
    private MockObject $commandBus;
    private ResourceUsage $resourceUsage;
    private MockObject $logger;
    private MockObject $migrationRunner;

    public function testExecute(): void
    {
        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            });

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $this->migrationRunner
            ->expects($this->never())
            ->method('run');

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
        $this->assertMatchesJsonSnapshot(Json::encode($dispatchedCommands));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importStravaDataConsoleCommand = new ImportStravaDataConsoleCommand(
            $this->commandBus = $this->createMock(CommandBus::class),
            $this->resourceUsage = new FixedResourceUsage(),
            $this->logger = $this->createMock(LoggerInterface::class),
            new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            $this->getConnection(),
            $this->migrationRunner = $this->createMock(MigrationRunner::class),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->importStravaDataConsoleCommand;
    }
}
