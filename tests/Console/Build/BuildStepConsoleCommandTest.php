<?php

namespace App\Tests\Console\Build;

use App\Console\Build\BuildStepConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Console\ConsoleOutputSnapshotDriver;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BuildStepConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private BuildStepConsoleCommand $buildStepConsoleCommand;
    private MockObject $commandBus;

    public function testExecute(): void
    {
        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            });

        $command = $this->getCommandInApplication('app:strava:build-step');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'step' => 'index',
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
        $this->assertMatchesJsonSnapshot(Json::encode($dispatchedCommands));
    }

    public function testExecuteWithStepWithoutDateParameter(): void
    {
        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            });

        $command = $this->getCommandInApplication('app:strava:build-step');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'step' => 'manifest',
        ]);

        $this->assertMatchesJsonSnapshot(Json::encode($dispatchedCommands));
    }

    public function testExecuteWithInvalidStep(): void
    {
        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:build-step');
        $commandTester = new CommandTester($command);

        $this->expectException(\ValueError::class);

        $commandTester->execute([
            'command' => $command->getName(),
            'step' => 'invalid-step',
        ]);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildStepConsoleCommand = new BuildStepConsoleCommand(
            $this->commandBus = $this->createMock(CommandBus::class),
            PausedClock::fromString('2025-12-04'),
            new FixedResourceUsage(),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildStepConsoleCommand;
    }
}
