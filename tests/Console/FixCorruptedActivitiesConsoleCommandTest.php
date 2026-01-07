<?php

namespace App\Tests\Console;

use App\Console\FixCorruptedActivitiesConsoleCommand;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class FixCorruptedActivitiesConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private FixCorruptedActivitiesConsoleCommand $fixCorruptedActivitiesConsoleCommand;

    public function testExecuteWithoutCorruptedData(): void
    {
        $command = $this->getCommandInApplication('app:data:fix-corrupted-activities');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixCorruptedActivitiesConsoleCommand = $this->getContainer()->get(FixCorruptedActivitiesConsoleCommand::class);
    }

    protected function getConsoleCommand(): Command
    {
        return $this->fixCorruptedActivitiesConsoleCommand;
    }
}
