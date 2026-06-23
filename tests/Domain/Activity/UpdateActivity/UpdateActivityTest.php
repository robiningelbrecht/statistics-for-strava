<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityName;
use App\Domain\Activity\UpdateActivity\UpdateActivity;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use PHPUnit\Framework\TestCase;

class UpdateActivityTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
        ]);

        $this->assertEquals(ActivityId::fromUnprefixed('1'), $command->getActivityId());
        $this->assertEquals(ActivityName::fromString('My custom activity'), $command->getName());
    }

    public function testFromPayloadTrimsName(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => '  My custom activity  ',
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
        ]);
    }
}
