<?php

namespace App\Tests\Console\Build;

use App\Application\AppUrl;
use App\Console\Build\BuildAppConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Console\ConsoleOutputSnapshotDriver;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BuildAppConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private BuildAppConsoleCommand $buildAppConsoleCommand;
    private MockObject $commandBus;
    private MockObject $logger;

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

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
        $this->assertMatchesJsonSnapshot(Json::encode($dispatchedCommands));
    }

    public function testExecuteWhenExceptionIsThrown(): void
    {
        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('OH NO ERROR'));

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->expectExceptionObject(new \RuntimeException('OH NO ERROR'));

        $command = $this->getCommandInApplication('app:strava:build-files');
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

        $this->commandBus = $this->createMock(CommandBus::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->buildAppConsoleCommand = new BuildAppConsoleCommand(
            commandBus: $this->commandBus,
            resourceUsage: new FixedResourceUsage(),
            appUrl: AppUrl::fromString('https://localhost'),
            logger: $this->logger,
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            )
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildAppConsoleCommand;
    }
}
