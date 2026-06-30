<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance\Log\AddGearMaintenanceLog;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Maintenance\Log\AddGearMaintenanceLog\AddGearMaintenanceLog;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\TestCase;

class AddGearMaintenanceLogTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = AddGearMaintenanceLog::fromPayload([
            'gearId' => '  '.GearId::fromUnprefixed('b1').'  ',
            'maintenanceTaskId' => '  '.MaintenanceTaskId::fromUnprefixed('chain-lubed').'  ',
            'performedOn' => '  2025-01-01 00:00:00  ',
        ]);

        $this->assertEquals(GearId::fromUnprefixed('b1'), $command->getGearId());
        $this->assertEquals(MaintenanceTaskId::fromUnprefixed('chain-lubed'), $command->getMaintenanceTaskId());
        $this->assertEquals(SerializableDateTime::fromString('2025-01-01 00:00:00'), $command->getPerformedOn());
    }

    public function testFromPayloadThrowsWhenGearIdMissing(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearId", "maintenanceTaskId" and "performedOn" are required.'));

        AddGearMaintenanceLog::fromPayload([
            'maintenanceTaskId' => 'chain-lubed',
            'performedOn' => '2025-01-01 00:00:00',
        ]);
    }

    public function testFromPayloadThrowsWhenGearIdNotAString(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearId", "maintenanceTaskId" and "performedOn" are required.'));

        AddGearMaintenanceLog::fromPayload([
            'gearId' => ['b1'],
            'maintenanceTaskId' => 'chain-lubed',
            'performedOn' => '2025-01-01 00:00:00',
        ]);
    }

    public function testFromPayloadThrowsWhenGearIdEmpty(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The "gearId" and "maintenanceTaskId" cannot be empty.'));

        AddGearMaintenanceLog::fromPayload([
            'gearId' => '   ',
            'maintenanceTaskId' => 'chain-lubed',
            'performedOn' => '2025-01-01 00:00:00',
        ]);
    }

    public function testFromPayloadThrowsWhenMaintenanceTaskIdEmpty(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The "gearId" and "maintenanceTaskId" cannot be empty.'));

        AddGearMaintenanceLog::fromPayload([
            'gearId' => 'b1',
            'maintenanceTaskId' => '   ',
            'performedOn' => '2025-01-01 00:00:00',
        ]);
    }

    public function testFromPayloadThrowsOnInvalidPerformedOn(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The "performedOn" is not a valid date.'));

        AddGearMaintenanceLog::fromPayload([
            'gearId' => 'b1',
            'maintenanceTaskId' => 'chain-lubed',
            'performedOn' => 'not-a-date',
        ]);
    }
}
