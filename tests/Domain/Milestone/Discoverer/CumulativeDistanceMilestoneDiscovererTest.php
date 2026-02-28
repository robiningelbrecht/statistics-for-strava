<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Milestone\Context\CumulativeDistanceContext;
use App\Domain\Milestone\Discoverer\CumulativeDistanceMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class CumulativeDistanceMilestoneDiscovererTest extends ContainerTestCase
{
    public function testDiscoverWithNoActivities(): void
    {
        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstMetricThreshold(): void
    {
        // 100 km threshold: insert exactly 100 km
        $this->insertActivity(1, '2024-01-01', 100.0);

        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::CUMULATIVE_DISTANCE, $milestone->getCategory());
        $this->assertEquals('100 km', $milestone->getTitle());
        $this->assertNull($milestone->getPrevious());
        $this->assertNull($milestone->getActivityId());

        $context = $milestone->getContext();
        $this->assertInstanceOf(CumulativeDistanceContext::class, $context);
        $this->assertInstanceOf(Kilometer::class, $context->threshold);
        $this->assertEquals(100.0, $context->threshold->toFloat());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        // First activity: 250 km → hits 100 + 250
        $this->insertActivity(1, '2024-01-01', 250.0);
        // Second activity: 260 km → total 510 km → hits 500
        $this->insertActivity(2, '2024-01-02', 260.0);

        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );
        $milestones = $discoverer->discover();

        $titles = array_map(fn ($m) => $m->getTitle(), $milestones->toArray());
        $this->assertEquals(['100 km', '250 km', '500 km'], $titles);

        $thirdMilestone = $milestones->toArray()[2];
        $this->assertNotNull($thirdMilestone->getPrevious());
        $this->assertEquals('250 km', $thirdMilestone->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroDistance(): void
    {
        $this->insertActivity(1, '2024-01-01', 0.0);

        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithImperialUnits(): void
    {
        // 161 km ≈ 100 mi, should hit the 100 mi threshold
        $this->insertActivity(1, '2024-01-01', 161.0);

        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::IMPERIAL,
        );
        $milestones = $discoverer->discover();

        $this->assertGreaterThanOrEqual(1, count($milestones));
        $this->assertStringContainsString('mi', $milestones->toArray()[0]->getTitle());
    }

    public function testFunComparisonIsSet(): void
    {
        $this->insertActivity(1, '2024-01-01', 500.0);

        $discoverer = new CumulativeDistanceMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );
        $milestones = $discoverer->discover();

        $last = $milestones->toArray()[count($milestones) - 1];
        $this->assertNotNull($last->getFunComparison());
    }

    private function insertActivity(int $id, string $date, float $distanceKm): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withDistance(Kilometer::from($distanceKm))
                ->build(), []
        ));
    }
}
