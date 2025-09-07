<?php

namespace App\Tests\Console;

use App\Console\ListAvailableQueryHandlersConsoleCommand;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ListAvailableQueryHandlersConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private ListAvailableQueryHandlersConsoleCommand $listAvailableQueryHandlersConsoleCommand;

    public function testExecute(): void
    {
        $command = $this->getCommandInApplication('app:query-bus:handlers');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->listAvailableQueryHandlersConsoleCommand = new ListAvailableQueryHandlersConsoleCommand(
            $this->getContainer()->get(QueryBus::class),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->listAvailableQueryHandlersConsoleCommand;
    }
}
