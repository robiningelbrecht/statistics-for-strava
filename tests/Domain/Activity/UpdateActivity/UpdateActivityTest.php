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

    public function testFromPayloadWithoutImagesKeyLeavesImagesUntouched(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
        ]);

        $this->assertSame([], $command->getNewImages());
        $this->assertSame([], $command->getRemovedImages());
    }

    public function testFromPayloadWithEmptyImages(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => '[]',
        ]);

        $this->assertSame([], $command->getNewImages());
        $this->assertSame([], $command->getRemovedImages());
    }

    public function testFromPayloadParsesNewAndRemovedImagesAndIgnoresUnchanged(): void
    {
        $command = UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => json_encode([
                ['status' => 'new', 'filename' => 'photo.jpg', 'content' => base64_encode('binary-content')],
                ['status' => 'unchanged', 'path' => '/files/activities/existing.png'],
                ['status' => 'removed', 'path' => '/files/activities/gone.png'],
            ]),
        ]);

        $newImages = $command->getNewImages();
        $this->assertCount(1, $newImages);
        $this->assertSame('jpg', $newImages[0]->getFilename()->getExtension());
        $this->assertSame('binary-content', $newImages[0]->getContent());

        $removedImages = $command->getRemovedImages();
        $this->assertCount(1, $removedImages);
        $this->assertSame('files/activities/gone.png', $removedImages[0]->getPath()->toLocalImagePath());
    }

    public function testFromPayloadThrowsOnUnsupportedImageType(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('Unsupported image file type.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => json_encode([
                ['status' => 'new', 'filename' => 'malware.php', 'content' => base64_encode('binary-content')],
            ]),
        ]);
    }

    public function testFromPayloadThrowsOnInvalidImageContent(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A new image has invalid "content".'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => json_encode([
                ['status' => 'new', 'filename' => 'photo.jpg', 'content' => 'not-valid-base64!!!'],
            ]),
        ]);
    }

    public function testFromPayloadThrowsOnUnknownImageStatus(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('Each image requires a valid "status".'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => json_encode([
                ['status' => 'whatever'],
            ]),
        ]);
    }

    public function testFromPayloadThrowsWhenImagesIsNotAString(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The "images" field is invalid.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => ['not', 'a', 'string'],
        ]);
    }

    public function testFromPayloadThrowsWhenImagesDoesNotDecodeToAnArray(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The "images" field is invalid.'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => '123',
        ]);
    }

    public function testFromPayloadThrowsWhenNewImageIsMissingFilenameOrContent(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A new image requires a "filename" and "content".'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => json_encode([
                ['status' => 'new'],
            ]),
        ]);
    }

    public function testFromPayloadThrowsWhenRemovedImageIsMissingPath(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A removed image requires a "path".'));

        UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'My custom activity',
            'sportType' => 'Ride',
            'images' => json_encode([
                ['status' => 'removed'],
            ]),
        ]);
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
