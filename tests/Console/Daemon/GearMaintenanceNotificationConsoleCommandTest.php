<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppUrl;
use App\Console\Daemon\GearMaintenanceNotificationConsoleCommand;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GearMaintenanceNotificationConsoleCommandTest extends ConsoleCommandTestCase
{
    private GearMaintenanceNotificationConsoleCommand $command;
    private SpyCommandBus $commandBus;

    public function testDoesNotNotifyWhenNoMaintenanceIsDue(): void
    {
        $command = $this->getCommandInApplication('app:cron:gear-maintenance-notification');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertEmpty($this->commandBus->getDispatchedCommands());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new GearMaintenanceNotificationConsoleCommand(
            $this->getContainer()->get(MaintenanceTaskProgressCalculator::class),
            AppUrl::fromString('http://localhost'),
            $this->commandBus = new SpyCommandBus(),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
