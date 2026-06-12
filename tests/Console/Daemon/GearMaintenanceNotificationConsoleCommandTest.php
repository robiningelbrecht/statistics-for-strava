<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppUrl;
use App\Console\Daemon\GearMaintenanceNotificationConsoleCommand;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GearMaintenanceNotificationConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private GearMaintenanceNotificationConsoleCommand $command;
    private SpyCommandBus $commandBus;

    public function testNotifiesWhenGearMaintenanceIsDue(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed('10130856'))
            ->build();
        $this->getContainer()->get(ImportedGearRepository::class)->save($gear);

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName(Name::fromString('#sfs-chain-lubed'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-02 00:00:00'))
                ->withDistance(Kilometer::from(600))
                ->build(),
            []
        ));

        $command = $this->getCommandInApplication('app:cron:gear-maintenance-notification');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

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
