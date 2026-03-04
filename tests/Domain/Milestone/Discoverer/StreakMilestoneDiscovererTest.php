<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Milestone\Context\StreakContext;
use App\Domain\Milestone\Discoverer\StreakMilestoneDiscoverer;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;
use Spatie\Snapshots\MatchesSnapshots;

class StreakMilestoneDiscovererTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private StreakMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverSevenDayStreak(): void
    {
        for ($i = 0; $i < 7; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $milestones = $this->discoverer->discover();

        $context = $milestones->getFirst()->getContext();
        $this->assertInstanceOf(StreakContext::class, $context);
        $this->assertEquals(7, $context->getDays());

        $this->assertMatchesJsonSnapshot(Json::encode($milestones));
    }

    public function testDiscoverMultipleThresholds(): void
    {
        for ($i = 0; $i < 14; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $milestones = $this->discoverer->discover();
        $this->assertMatchesJsonSnapshot(Json::encode($milestones));
    }

    public function testDiscoverNoMilestoneForShortStreak(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $this->assertTrue($this->discoverer->discover()->isEmpty());
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

        $milestones = $this->discoverer->discover();
        $this->assertMatchesJsonSnapshot(Json::encode($milestones));
    }

    public function testDiscoverHandlesDuplicateDaysInStreak(): void
    {
        for ($i = 0; $i < 7; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }
        $this->insertActivity(100, '2024-01-03');

        $milestones = $this->discoverer->discover();
        $this->assertMatchesJsonSnapshot(Json::encode($milestones));
    }

    public function testFunComparisonIsSet(): void
    {
        for ($i = 0; $i < 21; ++$i) {
            $this->insertActivity($i + 1, sprintf('2024-01-%02d', $i + 1));
        }

        $milestones = $this->discoverer->discover();

        $twentyOneDayMilestone = null;
        foreach ($milestones->toArray() as $milestone) {
            if (21 === $milestone->getContext()->getDays()) {
                $twentyOneDayMilestone = $milestone;
            }
        }

        $this->assertNotNull($twentyOneDayMilestone);
        $this->assertNotNull($twentyOneDayMilestone->getFunComparison());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new StreakMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
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
