<?php

namespace App\Tests\Console;

use App\Console\ImportStravaDataConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\DependencyInjection\YamlConfigFiles;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\FileSystem\UnwritablePermissionChecker;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use Doctrine\DBAL\Connection;
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
    private MockObject $migrationRunner;
    private MockObject $connection;
    private ResourceUsage $resourceUsage;
    private MockObject $logger;

    public function testExecute(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands) {
                $dispatchedCommands[] = $command;
            });

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('VACUUM');

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
        $this->assertMatchesJsonSnapshot(Json::encode($dispatchedCommands));
    }

    public function testExecuteWithMaxStravaUsageReached(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('VACUUM');

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testExecuteWithInsufficientPermissions(): void
    {
        $this->importStravaDataConsoleCommand = new ImportStravaDataConsoleCommand(
            $this->commandBus,
            new UnwritablePermissionChecker(),
            $this->migrationRunner,
            $this->getContainer()->get(YamlConfigFiles::class),
            $this->resourceUsage,
            $this->connection,
            $this->logger
        );

        $this->migrationRunner
            ->expects($this->never())
            ->method('run');

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->connection
            ->expects($this->never())
            ->method('executeStatement')
            ->with('VACUUM');

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importStravaDataConsoleCommand = new ImportStravaDataConsoleCommand(
            $this->commandBus = $this->createMock(CommandBus::class),
            new SuccessfulPermissionChecker(),
            $this->migrationRunner = $this->createMock(MigrationRunner::class),
            $this->getContainer()->get(YamlConfigFiles::class),
            $this->resourceUsage = new FixedResourceUsage(),
            $this->connection = $this->createMock(Connection::class),
            $this->logger = $this->createMock(LoggerInterface::class),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->importStravaDataConsoleCommand;
    }
}
