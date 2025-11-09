<?php

namespace App\Tests\Console\Daemon;

use App\Console\Daemon\RunCronActionConsoleCommand;
use App\Infrastructure\Daemon\Cron\Cron;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Infrastructure\Doctrine\Migrations\VoidMigrationRunner;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunCronActionConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private RunCronActionConsoleCommand $runCronActionCommand;
    private MigrationRunner $migrationRunner;

    public function testExecute(): void
    {
        $command = $this->getCommandInApplication('app:cron:action');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'cronActionId' => 'fake',
        ]);

        $this->assertMatchesTextSnapshot(str_replace(' ', '', $commandTester->getDisplay()));
    }

    public function testExecuteWithConnectionException(): void
    {
        $this->migrationRunner->throwOnNextRun();

        $command = $this->getCommandInApplication('app:cron:action');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'cronActionId' => 'fake',
        ]);

        $this->assertMatchesTextSnapshot(str_replace(' ', '', $commandTester->getDisplay()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->runCronActionCommand = new RunCronActionConsoleCommand(
            $this->getContainer()->get(Cron::class),
            $this->migrationRunner = new VoidMigrationRunner(),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->runCronActionCommand;
    }
}
