<?php

namespace App\Tests\Console\Debug;

use App\Console\Debug\DebugEnvironmentConsoleCommand;
use App\Tests\Console\ConsoleCommandTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DebugEnvironmentConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private DebugEnvironmentConsoleCommand $debugEnvironmentConsoleCommand;

    public function testExecute(): void
    {
        $command = $this->getCommandInApplication('app:debug:environment');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertStringContainsString('Please copy all this output into the description of the bug ticket', $commandTester->getDisplay());
        $this->assertStringContainsString('APP_VERSION', $commandTester->getDisplay());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->debugEnvironmentConsoleCommand = new DebugEnvironmentConsoleCommand();
    }

    protected function getConsoleCommand(): Command
    {
        return $this->debugEnvironmentConsoleCommand;
    }
}
