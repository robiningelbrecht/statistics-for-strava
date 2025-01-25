<?php

namespace App\Tests\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\Split\ActivitySplits;
use App\Domain\Strava\Activity\Split\DbalActivitySplitRepository;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;

class DbalActivitySplitRepositoryTest extends ContainerTestCase
{
    private ActivitySplitRepository $activitySplitRepository;

    public function testAddAndFindBy(): void
    {
        $activitySplitOne = ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(1)
            ->build();
        $this->activitySplitRepository->add($activitySplitOne);

        $activitySplitTwo = ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(2)
            ->build();
        $this->activitySplitRepository->add($activitySplitTwo);

        $activitySplitThree = ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(3)
            ->build();
        $this->activitySplitRepository->add($activitySplitThree);

        $this->activitySplitRepository->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test2'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(3)
            ->build());

        $this->activitySplitRepository->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::IMPERIAL)
            ->withSplitNumber(3)
            ->build());

        $this->assertEquals(
            ActivitySplits::fromArray([$activitySplitOne, $activitySplitTwo, $activitySplitThree]),
            $this->activitySplitRepository->findBy(
                activityId: ActivityId::fromUnprefixed('test'),
                unitSystem: UnitSystem::METRIC
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activitySplitRepository = new DbalActivitySplitRepository($this->getConnection());
    }
}
