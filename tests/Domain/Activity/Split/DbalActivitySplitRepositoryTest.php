<?php

namespace App\Tests\Domain\Activity\Split;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\Split\ActivitySplits;
use App\Domain\Activity\Split\DbalActivitySplitRepository;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;

class DbalActivitySplitRepositoryTest extends ContainerTestCase
{
    private ActivitySplitRepository $activitySplitRepository;

    public function testAddAndFindBy(): void
    {
        $activitySplitTwo = ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(2)
            ->build();
        $this->activitySplitRepository->add($activitySplitTwo);

        $activitySplitOne = ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(1)
            ->build();
        $this->activitySplitRepository->add($activitySplitOne);

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

    public function testIsImportedForActivity(): void
    {
        $this->activitySplitRepository->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::IMPERIAL)
            ->withSplitNumber(3)
            ->build()
        );

        $this->assertTrue($this->activitySplitRepository->isImportedForActivity(ActivityId::fromUnprefixed('test')));
        $this->assertFalse($this->activitySplitRepository->isImportedForActivity(ActivityId::fromUnprefixed('test2')));
    }

    public function testDeleteForActivity(): void
    {
        $this->activitySplitRepository->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(1)
            ->build());

        $this->activitySplitRepository->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(2)
            ->build());

        $this->activitySplitRepository->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->withSplitNumber(3)
            ->build());

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

        $this->activitySplitRepository->deleteForActivity(ActivityId::fromUnprefixed('test'));

        $this->assertEquals(
            1,
            $this->getConnection()->executeQuery('SELECT COUNT(*) FROM ActivitySplit')->fetchOne()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activitySplitRepository = new DbalActivitySplitRepository($this->getConnection());
    }
}
