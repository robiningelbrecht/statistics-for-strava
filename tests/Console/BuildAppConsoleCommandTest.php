<?php

namespace App\Tests\Console;

use App\BuildApp\AppUrl;
use App\Console\BuildAppConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\Serialization\Json;
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
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands) {
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
            logger: $this->logger
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildAppConsoleCommand;
    }
}
