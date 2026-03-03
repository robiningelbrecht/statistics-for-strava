<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeDistanceContext;
use App\Domain\Milestone\Discoverer\CumulativeDistanceMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class CumulativeDistanceMilestoneDiscovererTest extends ContainerTestCase
{
    private CumulativeDistanceMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstMetricThreshold(): void
    {
        $this->insertActivity(1, '2024-01-01', 100.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $globalMilestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::CUMULATIVE_DISTANCE, $globalMilestone->getCategory());
        $this->assertNull($globalMilestone->getSportType());
        $this->assertNull($globalMilestone->getPrevious());
        $this->assertNull($globalMilestone->getActivityId());

        $context = $globalMilestone->getContext();
        $this->assertInstanceOf(CumulativeDistanceContext::class, $context);
        $this->assertInstanceOf(Kilometer::class, $context->getThreshold());
        $this->assertEquals(100.0, $context->getThreshold()->toFloat());

        $sportMilestone = $milestones->toArray()[1];
        $this->assertEquals(SportType::RIDE, $sportMilestone->getSportType());
        $this->assertNull($sportMilestone->getPrevious());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        $this->insertActivity(1, '2024-01-01', 250.0);
        $this->insertActivity(2, '2024-01-02', 260.0);

        $milestones = $this->discoverer->discover();

        $global500 = $milestones->toArray()[4];
        $this->assertNull($global500->getSportType());
        $this->assertNotNull($global500->getPrevious());
        $this->assertEquals('250 km', $global500->getPrevious()->getLabel());

        $sport500 = $milestones->toArray()[5];
        $this->assertEquals(SportType::RIDE, $sport500->getSportType());
        $this->assertNotNull($sport500->getPrevious());
        $this->assertEquals('250 km', $sport500->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroDistance(): void
    {
        $this->insertActivity(1, '2024-01-01', 0.0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithImperialUnits(): void
    {
        $this->insertActivity(1, '2024-01-01', 161.0);

        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::IMPERIAL,
            new IncrementingMilestoneIdFactory(),
        );
        $milestones = $discoverer->discover();

        $this->assertGreaterThanOrEqual(2, count($milestones));
    }

    public function testDiscoverWithMultipleSportTypes(): void
    {
        $this->insertActivityWithSportType(1, '2024-01-01', 150.0, SportType::RIDE);
        $this->insertActivityWithSportType(2, '2024-01-02', 110.0, SportType::RUN);

        $milestones = $this->discoverer->discover();

        $milestonesArray = $milestones->toArray();

        $this->assertNull($milestonesArray[0]->getSportType());

        $this->assertEquals(SportType::RIDE, $milestonesArray[1]->getSportType());

        $this->assertNull($milestonesArray[2]->getSportType());

        $this->assertEquals(SportType::RUN, $milestonesArray[3]->getSportType());
    }

    public function testFunComparisonIsSet(): void
    {
        $this->insertActivity(1, '2024-01-01', 500.0);

        $milestones = $this->discoverer->discover();

        $last = $milestones->toArray()[count($milestones) - 1];
        $this->assertNotNull($last->getFunComparison());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
            new IncrementingMilestoneIdFactory(),
        );
    }

    private function insertActivity(int $id, string $date, float $distanceKm): void
    {
        $this->insertActivityWithSportType($id, $date, $distanceKm, SportType::RIDE);
    }

    private function insertActivityWithSportType(int $id, string $date, float $distanceKm, SportType $sportType): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withDistance(Kilometer::from($distanceKm))
                ->withSportType($sportType)
                ->build(), []
        ));
    }
}
