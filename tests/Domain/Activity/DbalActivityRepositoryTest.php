<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\DbalActivityRepository;
use App\Domain\Activity\DbalActivityWithRawDataRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class DbalActivityRepositoryTest extends ContainerTestCase
{
    private ActivityRepository $activityRepository;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testFind(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            $activityOne,
            $this->activityRepository->find($activityOne->getId())
        );

        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            $activityTwo,
            $this->activityRepository->find($activityTwo->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectExceptionObject(new EntityNotFound('Activity "activity-1" not found'));
        $this->activityRepository->find(ActivityId::fromUnprefixed(1));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityRepository = new DbalActivityRepository(
            $this->getConnection(),
        );
        $this->activityWithRawDataRepository = new DbalActivityWithRawDataRepository(
            $this->getConnection(),
            $this->activityRepository,
        );
    }
}
