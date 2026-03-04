<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Milestone\Context\GearElevationContext;
use App\Domain\Milestone\Discoverer\GearElevationMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class GearElevationMilestoneDiscovererTest extends ContainerTestCase
{
    private GearElevationMilestoneDiscoverer $discoverer;

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
                ->withElevation(Meter::from(1000))
                ->build(), []
        ));

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstThreshold(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 500.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::GEAR_ELEVATION, $milestone->getCategory());
        $this->assertNull($milestone->getSportType());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(GearElevationContext::class, $context);
        $this->assertEquals('Canyon Endurace', $context->getGearName());
        $this->assertEquals(500.0, $context->getThreshold()->toFloat());
    }

    public function testDiscoverMultipleThresholdsWithPreviousChain(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 1500.0);
        $this->insertActivity('2', '2024-01-02', $gearId, 1500.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(3, $milestones);

        $milestonesArray = $milestones->toArray();
        $this->assertNull($milestonesArray[0]->getPrevious());
        $this->assertNotNull($milestonesArray[1]->getPrevious());
        $this->assertEquals(Meter::from(500), $milestonesArray[1]->getPrevious()->getThreshold());
        $this->assertNotNull($milestonesArray[2]->getPrevious());
        $this->assertEquals(Meter::from(1000), $milestonesArray[2]->getPrevious()->getThreshold());
    }

    public function testDiscoverTracksGearsSeparately(): void
    {
        $bikeId = GearId::fromUnprefixed('bike-1');
        $shoesId = GearId::fromUnprefixed('shoes-1');
        $this->insertGear($bikeId, 'Canyon Endurace');
        $this->insertGear($shoesId, 'Nike Pegasus');

        $this->insertActivity('1', '2024-01-01', $bikeId, 500.0);
        $this->insertActivity('2', '2024-01-02', $shoesId, 500.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);
    }

    public function testDiscoverSkipsZeroElevation(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 0.0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithImperialUnits(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 500.0);

        $discoverer = new GearElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::IMPERIAL,
            new IncrementingMilestoneIdFactory(),
        );
        $milestones = $discoverer->discover();

        $this->assertGreaterThanOrEqual(1, count($milestones));

        $context = $milestones->toArray()[0]->getContext();
        $this->assertInstanceOf(GearElevationContext::class, $context);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new GearElevationMilestoneDiscoverer(
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

    private function insertActivity(string $id, string $date, GearId $gearId, float $elevationM): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withElevation(Meter::from($elevationM))
                ->withGearId($gearId)
                ->build(), []
        ));
    }
}
