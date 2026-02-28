<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Milestone\Context\CumulativeElevationContext;
use App\Domain\Milestone\Discoverer\CumulativeElevationMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class CumulativeElevationMilestoneDiscovererTest extends ContainerTestCase
{
    public function testDiscoverWithNoActivities(): void
    {
        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstMetricThreshold(): void
    {
        $this->insertActivity(1, '2024-01-01', 500.0);

        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::CUMULATIVE_ELEVATION, $milestone->getCategory());
        $this->assertEquals('500 m', $milestone->getTitle());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(CumulativeElevationContext::class, $context);
        $this->assertInstanceOf(Meter::class, $context->threshold);
        $this->assertEquals(500.0, $context->threshold->toFloat());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        $this->insertActivity(1, '2024-01-01', 600.0);
        $this->insertActivity(2, '2024-01-02', 500.0);

        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );
        $milestones = $discoverer->discover();

        $titles = array_map(fn ($m) => $m->getTitle(), $milestones->toArray());
        $this->assertEquals(['500 m', '1,000 m'], $titles);

        $secondMilestone = $milestones->toArray()[1];
        $this->assertNotNull($secondMilestone->getPrevious());
        $this->assertEquals('500 m', $secondMilestone->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroElevation(): void
    {
        $this->insertActivity(1, '2024-01-01', 0.0);

        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithImperialUnits(): void
    {
        // 500 m ≈ 1640 ft, which should hit the 1,000 ft threshold
        $this->insertActivity(1, '2024-01-01', 500.0);

        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::IMPERIAL,
        );
        $milestones = $discoverer->discover();

        $this->assertGreaterThanOrEqual(1, count($milestones));
        $this->assertStringContainsString('ft', $milestones->toArray()[0]->getTitle());
    }

    public function testFunComparisonIsSet(): void
    {
        $this->insertActivity(1, '2024-01-01', 1000.0);

        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
        );
        $milestones = $discoverer->discover();

        $last = $milestones->toArray()[count($milestones) - 1];
        $this->assertNotNull($last->getFunComparison());
    }

    private function insertActivity(int $id, string $date, float $elevationM): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withElevation(Meter::from($elevationM))
                ->build(), []
        ));
    }
}
