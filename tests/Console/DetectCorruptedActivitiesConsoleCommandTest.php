<?php

namespace App\Tests\Console;

use App\Console\DetectCorruptedActivitiesConsoleCommand;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DetectCorruptedActivitiesConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private DetectCorruptedActivitiesConsoleCommand $detectCorruptedActivitiesConsoleCommand;

    public function testExecuteWithoutCorruptedData(): void
    {
        $command = $this->getCommandInApplication('app:data:detect-corrupted-activities');
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

        $this->detectCorruptedActivitiesConsoleCommand = $this->getContainer()->get(DetectCorruptedActivitiesConsoleCommand::class);
    }

    protected function getConsoleCommand(): Command
    {
        return $this->detectCorruptedActivitiesConsoleCommand;
    }
}
