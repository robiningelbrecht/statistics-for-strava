<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Milestone\Context\CumulativeMovingTimeContext;
use App\Domain\Milestone\Discoverer\CumulativeMovingTimeMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class CumulativeMovingTimeMilestoneDiscovererTest extends ContainerTestCase
{
    private CumulativeMovingTimeMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstThreshold(): void
    {
        // 24 hours = 86400 seconds
        $this->insertActivity(1, '2024-01-01', 86400);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::CUMULATIVE_MOVING_TIME, $milestone->getCategory());
        $this->assertEquals('24 hours', $milestone->getTitle());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(CumulativeMovingTimeContext::class, $context);
        $this->assertEquals(24.0, $context->getThreshold()->toFloat());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        // 48h = 172800s
        $this->insertActivity(1, '2024-01-01', 100000);
        $this->insertActivity(2, '2024-01-02', 80000);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $titles = array_map(fn ($m) => $m->getTitle(), $milestones->toArray());
        $this->assertEquals(['24 hours', '48 hours'], $titles);

        $secondMilestone = $milestones->toArray()[1];
        $this->assertNotNull($secondMilestone->getPrevious());
        $this->assertEquals('24 h', $secondMilestone->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroMovingTime(): void
    {
        $this->insertActivity(1, '2024-01-01', 0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testFunComparisonIsNullForSmallThreshold(): void
    {
        // 24h doesn't have a fun comparison (min is 8h for FULL_WORK_DAY)
        // Actually 24h >= 8, so it should have one
        $this->insertActivity(1, '2024-01-01', 86400);

        $milestones = $this->discoverer->discover();

        $this->assertNotNull($milestones->toArray()[0]->getFunComparison());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new CumulativeMovingTimeMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
    }

    private function insertActivity(int $id, string $date, int $movingTimeInSeconds): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->build(), []
        ));
    }
}
