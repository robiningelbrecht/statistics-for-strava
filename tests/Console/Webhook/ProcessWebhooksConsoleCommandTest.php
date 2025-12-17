<?php

namespace App\Tests\Console\Webhook;

use App\Application\importDataAndBuildAppCronAction;
use App\Console\Webhook\ProcessWebhooksConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Console\ConsoleOutputSnapshotDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessWebhooksConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;
    private ProcessWebhooksConsoleCommand $processWebhooksConsoleCommand;
    private CommandBus $commandBus;

    public function testExecute(): void
    {
        $command = $this->getCommandInApplication('app:cron:process-webhooks');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processWebhooksConsoleCommand = new ProcessWebhooksConsoleCommand(
            $this->getContainer()->get(importDataAndBuildAppCronAction::class)
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->processWebhooksConsoleCommand;
    }
}
