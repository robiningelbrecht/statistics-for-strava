<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance\Log\UpdateGearMaintenanceLog;

use App\Domain\Gear\Maintenance\Log\GearMaintenanceLogId;
use App\Domain\Gear\Maintenance\Log\UpdateGearMaintenanceLog\UpdateGearMaintenanceLog;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\TestCase;

class UpdateGearMaintenanceLogTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = UpdateGearMaintenanceLog::fromPayload([
            'gearMaintenanceLogId' => (string) GearMaintenanceLogId::fromUnprefixed('abc'),
            'performedOn' => '  2025-01-01 00:00:00  ',
        ]);

        $this->assertEquals(
            GearMaintenanceLogId::fromUnprefixed('abc'),
            $command->getGearMaintenanceLogId(),
        );
        $this->assertEquals(SerializableDateTime::fromString('2025-01-01 00:00:00'), $command->getPerformedOn());
    }

    public function testFromPayloadThrowsWhenGearMaintenanceLogIdMissing(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearMaintenanceLogId" and "performedOn" are required.'));

        UpdateGearMaintenanceLog::fromPayload([
            'performedOn' => '2025-01-01 00:00:00',
        ]);
    }

    public function testFromPayloadThrowsWhenPerformedOnMissing(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearMaintenanceLogId" and "performedOn" are required.'));

        UpdateGearMaintenanceLog::fromPayload([
            'gearMaintenanceLogId' => (string) GearMaintenanceLogId::fromUnprefixed('abc'),
        ]);
    }

    public function testFromPayloadThrowsWhenPerformedOnNotAString(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearMaintenanceLogId" and "performedOn" are required.'));

        UpdateGearMaintenanceLog::fromPayload([
            'gearMaintenanceLogId' => (string) GearMaintenanceLogId::fromUnprefixed('abc'),
            'performedOn' => 12345,
        ]);
    }

    public function testFromPayloadThrowsOnInvalidPerformedOn(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The "performedOn" is not a valid date.'));

        UpdateGearMaintenanceLog::fromPayload([
            'gearMaintenanceLogId' => (string) GearMaintenanceLogId::fromUnprefixed('abc'),
            'performedOn' => 'not-a-date',
        ]);
    }
}
