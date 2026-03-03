<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Milestone\Context\GearDistanceContext;
use App\Domain\Milestone\Context\GearElevationContext;
use App\Domain\Milestone\Context\GearMovingTimeContext;
use App\Domain\Milestone\Discoverer\GearMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class GearMilestoneDiscovererTest extends ContainerTestCase
{
    private GearMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithNoGear(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->withDistance(Kilometer::from(200))
                ->build(), []
        ));

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverDistanceMilestone(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivityWithGear('1', '2024-01-01', $gearId, 100.0, 0.0, 0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::GEAR_DISTANCE, $milestone->getCategory());
        $this->assertNull($milestone->getSportType());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(GearDistanceContext::class, $context);
        $this->assertEquals('Canyon Endurace', $context->getGearName());
        $this->assertEquals(100.0, $context->getThreshold()->toFloat());
    }

    public function testDiscoverElevationMilestone(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivityWithGear('1', '2024-01-01', $gearId, 0.0, 500.0, 0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::GEAR_ELEVATION, $milestone->getCategory());

        $context = $milestone->getContext();
        $this->assertInstanceOf(GearElevationContext::class, $context);
        $this->assertEquals('Canyon Endurace', $context->getGearName());
    }

    public function testDiscoverMovingTimeMilestone(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        // 24 hours = 86400 seconds
        $this->insertActivityWithGear('1', '2024-01-01', $gearId, 0.0, 0.0, 86400);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::GEAR_MOVING_TIME, $milestone->getCategory());

        $context = $milestone->getContext();
        $this->assertInstanceOf(GearMovingTimeContext::class, $context);
        $this->assertEquals('Canyon Endurace', $context->getGearName());
        $this->assertEquals(24.0, $context->getThreshold()->toFloat());
    }

    public function testDiscoverMultipleThresholdsWithPreviousChain(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivityWithGear('1', '2024-01-01', $gearId, 300.0, 0.0, 0);
        $this->insertActivityWithGear('2', '2024-01-02', $gearId, 250.0, 0.0, 0);

        $milestones = $this->discoverer->discover();

        $distanceMilestones = array_values(array_filter(
            $milestones->toArray(),
            fn ($m) => MilestoneCategory::GEAR_DISTANCE === $m->getCategory(),
        ));

        $this->assertCount(3, $distanceMilestones);

        $this->assertNull($distanceMilestones[0]->getPrevious());
        $this->assertNotNull($distanceMilestones[1]->getPrevious());
        $this->assertEquals('100 km', $distanceMilestones[1]->getPrevious()->getLabel());
        $this->assertNotNull($distanceMilestones[2]->getPrevious());
        $this->assertEquals('250 km', $distanceMilestones[2]->getPrevious()->getLabel());
    }

    public function testDiscoverTracksGearsSeparately(): void
    {
        $bikeId = GearId::fromUnprefixed('bike-1');
        $shoesId = GearId::fromUnprefixed('shoes-1');
        $this->insertGear($bikeId, 'Canyon Endurace');
        $this->insertGear($shoesId, 'Nike Pegasus');

        $this->insertActivityWithGear('1', '2024-01-01', $bikeId, 100.0, 0.0, 0);
        $this->insertActivityWithGear('2', '2024-01-02', $shoesId, 100.0, 0.0, 0);

        $milestones = $this->discoverer->discover();

        $distanceMilestones = array_values(array_filter(
            $milestones->toArray(),
            fn ($m) => MilestoneCategory::GEAR_DISTANCE === $m->getCategory(),
        ));

        $this->assertCount(2, $distanceMilestones);
    }

    public function testDiscoverCombinesDistanceElevationAndTime(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        // 100 km distance, 500 m elevation, 24h moving time
        $this->insertActivityWithGear('1', '2024-01-01', $gearId, 100.0, 500.0, 86400);

        $milestones = $this->discoverer->discover();

        $categories = array_map(fn ($m) => $m->getCategory(), $milestones->toArray());
        $this->assertContains(MilestoneCategory::GEAR_DISTANCE, $categories);
        $this->assertContains(MilestoneCategory::GEAR_ELEVATION, $categories);
        $this->assertContains(MilestoneCategory::GEAR_MOVING_TIME, $categories);
    }

    public function testDiscoverWithImperialUnits(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        // 161 km ≈ 100 mi
        $this->insertActivityWithGear('1', '2024-01-01', $gearId, 161.0, 0.0, 0);

        $discoverer = new GearMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::IMPERIAL,
            new IncrementingMilestoneIdFactory(),
        );
        $milestones = $discoverer->discover();

        $distanceMilestones = array_values(array_filter(
            $milestones->toArray(),
            fn ($m) => MilestoneCategory::GEAR_DISTANCE === $m->getCategory(),
        ));

        $this->assertGreaterThanOrEqual(1, count($distanceMilestones));
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new GearMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
            new IncrementingMilestoneIdFactory(),
        );
    }

    private function insertGear(GearId $gearId, string $name): void
    {
        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId($gearId)
                ->withName($name)
                ->build()
        );
    }

    private function insertActivityWithGear(
        string $id,
        string $date,
        GearId $gearId,
        float $distanceKm,
        float $elevationM,
        int $movingTimeInSeconds,
    ): void {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withDistance(Kilometer::from($distanceKm))
                ->withElevation(Meter::from($elevationM))
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->withGearId($gearId)
                ->build(), []
        ));
    }
}
