<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityName;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\UpdateActivity\UpdateActivity;
use App\Domain\Gear\GearId;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use PHPUnit\Framework\TestCase;

class UpdateActivityTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Run',
            'description' => 'A nice run',
            'deviceName' => 'Garmin Edge',
            'gearId' => 'gear-1',
            'isCommute' => 'true',
        ]);

        $this->assertEquals(ActivityId::fromUnprefixed('1'), $command->getActivityId());
        $this->assertEquals(ActivityName::fromString('My custom activity'), $command->getName());
        $this->assertSame(SportType::RUN, $command->getSportType());
        $this->assertSame('A nice run', $command->getDescription());
        $this->assertSame('Garmin Edge', $command->getDeviceName());
        $this->assertEquals(GearId::fromUnprefixed('1'), $command->getGearId());
        $this->assertTrue($command->isCommute());
    }

    public function testFromPayloadWithEmptyOptionalFields(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'description' => '   ',
            'deviceName' => '',
            'gearId' => '',
        ]);

        $this->assertNull($command->getDescription());
        $this->assertNull($command->getDeviceName());
        $this->assertNull($command->getGearId());
        $this->assertFalse($command->isCommute());
    }

    public function testFromPayloadThrowsOnMissingSportType(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A valid "sportType" is required.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
        ]);
    }

    public function testFromPayloadThrowsOnInvalidSportType(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A valid "sportType" is required.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'NotARealSport',
        ]);
    }

    public function testFromPayloadTrimsName(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => '  My custom activity  ',
            'sportType' => 'Ride',
        ]);

        $this->assertEquals(ActivityName::fromString('My custom activity'), $command->getName());
    }

    public function testFromPayloadThrowsOnMissingActivityId(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('An "activityId" and "name" are required.'));

        UpdateActivity::fromPayload([
            'name' => 'My custom activity',
        ]);
    }

    public function testFromPayloadThrowsOnMissingName(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('An "activityId" and "name" are required.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
        ]);
    }

    public function testFromPayloadThrowsOnEmptyName(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The name cannot be empty.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => '   ',
            'sportType' => 'Ride',
        ]);
    }
}
