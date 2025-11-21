<?php

namespace App\Tests\Domain\Activity\Device;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Device\DbalDeviceRepository;
use App\Domain\Activity\Device\Device;
use App\Domain\Activity\Device\DeviceRepository;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class DbalDeviceRepositoryTest extends ContainerTestCase
{
    private DeviceRepository $deviceRepository;

    public function testFindAll(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withDeviceName('Garmin Forerunner 945')
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withoutDeviceName()
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-3'))
                ->withDeviceName('Garmin Edge 530')
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-4'))
                ->withDeviceName('Garmin Edge 530')
                ->build(),
            []
        ));

        $this->assertEquals(
            [
                Device::create('Garmin Edge 530'),
                Device::create('Garmin Forerunner 945'),
            ],
            $this->deviceRepository->findAll(),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->deviceRepository = new DbalDeviceRepository($this->getConnection());
    }
}
