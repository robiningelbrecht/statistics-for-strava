<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Milestone\Context\StreakContext;
use App\Domain\Milestone\Discoverer\StreakMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class StreakMilestoneDiscovererTest extends ContainerTestCase
{
    public function testDiscoverWithNoActivities(): void
    {
        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverSevenDayStreak(): void
    {
        for ($i = 0; $i < 7; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::STREAK, $milestone->getCategory());
        $this->assertEquals('7 day streak', $milestone->getTitle());
        $this->assertNull($milestone->getSportType());
        $this->assertNull($milestone->getActivityId());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(StreakContext::class, $context);
        $this->assertEquals(7, $context->getDays());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        for ($i = 0; $i < 14; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(2, $milestones);

        $titles = array_map(fn ($m) => $m->getTitle(), $milestones->toArray());
        $this->assertEquals(['7 day streak', '14 day streak'], $titles);

        $secondMilestone = $milestones->toArray()[1];
        $this->assertNotNull($secondMilestone->getPrevious());
        $this->assertEquals('7 days', $secondMilestone->getPrevious()->getLabel());
    }

    public function testDiscoverNoMilestoneForShortStreak(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverResetsStreakOnGap(): void
    {
        // 5-day streak, then gap, then 7-day streak
        for ($i = 0; $i < 5; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }
        // Gap on Jan 6
        for ($i = 0; $i < 7; ++$i) {
            $this->insertActivity($i + 10, sprintf('2024-01-%02d', $i + 7));
        }

        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);
        $this->assertEquals('7 day streak', $milestones->toArray()[0]->getTitle());
    }

    public function testDiscoverHandlesDuplicateDaysInStreak(): void
    {
        // Two activities on same day should count as 1 day
        for ($i = 0; $i < 7; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }
        $this->insertActivity(100, '2024-01-03');

        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);
        $this->assertEquals('7 day streak', $milestones->toArray()[0]->getTitle());
    }

    public function testFunComparisonIsSet(): void
    {
        for ($i = 0; $i < 7; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $discoverer = new StreakMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertNotNull($milestones->toArray()[0]->getFunComparison());
    }

    private function insertActivity(int $id, string $date): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->build(), []
        ));
    }
}
