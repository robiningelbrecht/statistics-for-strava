<?php

namespace App\Tests\Console\Daemon;

use App\Console\Daemon\RunCronActionConsoleCommand;
use App\Infrastructure\Cron\Cron;
use App\Tests\Console\ConsoleCommandTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunCronActionConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private RunCronActionConsoleCommand $runCronActionCommand;

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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->runCronActionCommand = new RunCronActionConsoleCommand(
            $this->getContainer()->get(Cron::class)
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->runCronActionCommand;
    }
}
