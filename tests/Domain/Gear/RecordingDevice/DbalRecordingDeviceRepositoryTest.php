<?php

namespace App\Tests\Domain\Gear\RecordingDevice;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\RecordingDevice\DbalRecordingDeviceRepository;
use App\Domain\Gear\RecordingDevice\RecordingDevice;
use App\Domain\Gear\RecordingDevice\RecordingDeviceRepository;
use App\Domain\Gear\RecordingDevice\RecordingDevices;
use App\Domain\Gear\RecordingDevice\RecordingDevicesConfig;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class DbalRecordingDeviceRepositoryTest extends ContainerTestCase
{
    private RecordingDeviceRepository $recordingDeviceRepository;

    public function testFindAll(): void
    {
        $activityRepository = $this->getContainer()->get(ActivityRepository::class);

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withDeviceName('Garmin Edge 530')
                ->withDistance(Kilometer::from(10))
                ->withElevation(Meter::from(100))
                ->withMovingTimeInSeconds(3600)
                ->build(),
            []
        ));

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withDeviceName('Garmin Edge 530')
                ->withDistance(Kilometer::from(20))
                ->withElevation(Meter::from(200))
                ->withMovingTimeInSeconds(7200)
                ->build(),
            []
        ));

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withDeviceName('Garmin Forerunner 945')
                ->withDistance(Kilometer::from(5))
                ->withElevation(Meter::from(50))
                ->withMovingTimeInSeconds(1800)
                ->build(),
            []
        ));

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withoutDeviceName()
                ->build(),
            []
        ));

        $devices = $this->recordingDeviceRepository->findAll();

        $this->assertEquals(
            RecordingDevices::fromArray([
                RecordingDevice::fromState(
                    name: 'Garmin Edge 530',
                    timeTracked: Seconds::from(10800),
                    distanceTracked: Meter::from(30000)->toKilometer(),
                    elevationTracked: Meter::from(300),
                    activityCount: 2,
                ),
                RecordingDevice::fromState(
                    name: 'Garmin Forerunner 945',
                    timeTracked: Seconds::from(1800),
                    distanceTracked: Meter::from(5000)->toKilometer(),
                    elevationTracked: Meter::from(50),
                    activityCount: 1,
                ),
            ]),
            $devices,
        );
    }

    public function testFindAllWithNoActivities(): void
    {
        $this->assertEquals(
            RecordingDevices::empty(),
            $this->recordingDeviceRepository->findAll(),
        );
    }

    public function testFindAllExcludesActivitiesWithoutDeviceName(): void
    {
        $activityRepository = $this->getContainer()->get(ActivityRepository::class);

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withoutDeviceName()
                ->build(),
            []
        ));

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withoutDeviceName()
                ->build(),
            []
        ));

        $this->assertEquals(
            RecordingDevices::empty(),
            $this->recordingDeviceRepository->findAll(),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->recordingDeviceRepository = new DbalRecordingDeviceRepository(
            $this->getConnection(),
            $this->getContainer()->get(RecordingDevicesConfig::class),
        );
    }
}
