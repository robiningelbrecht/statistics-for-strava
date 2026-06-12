<?php

namespace App\Tests\Console\Daemon;

use App\Application\Import\RunStravaImportAndBuildAppCronAction;
use App\Console\Daemon\ProcessStravaWebhooksConsoleCommand;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Console\ConsoleOutputSnapshotDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessStravaWebhooksConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;
    private ProcessStravaWebhooksConsoleCommand $processWebhooksConsoleCommand;
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

        $this->processWebhooksConsoleCommand = new ProcessStravaWebhooksConsoleCommand(
            $this->getContainer()->get(RunStravaImportAndBuildAppCronAction::class)
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->processWebhooksConsoleCommand;
    }
}
