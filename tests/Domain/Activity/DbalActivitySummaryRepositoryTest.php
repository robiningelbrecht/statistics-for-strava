<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivitySummary;
use App\Domain\Activity\ActivitySummaryRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\DbalActivityWithRawDataRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;

class DbalActivitySummaryRepositoryTest extends ContainerTestCase
{
    private ActivitySummaryRepository $activitySummaryRepository;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activity,
            ['raw' => 'data']
        ));

        $persisted = $this->activitySummaryRepository->find($activity->getId());
        $this->assertEquals(
            ActivitySummary::create(
                activityId: $activity->getId(),
                name: $activity->getName(),
                startDateTime: $activity->getStartDate(),
            ),
            $persisted,
        );
    }

    public function testFindItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->activitySummaryRepository->find(ActivityId::fromUnprefixed(1));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activitySummaryRepository = $this->getContainer()->get(ActivitySummaryRepository::class);
        $this->activityWithRawDataRepository = new DbalActivityWithRawDataRepository(
            $this->getConnection(),
            $this->getContainer()->get(ActivityRepository::class)
        );
    }
}
