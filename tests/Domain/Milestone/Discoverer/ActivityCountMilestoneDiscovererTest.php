<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Milestone\Context\ActivityCountContext;
use App\Domain\Milestone\Discoverer\ActivityCountMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class ActivityCountMilestoneDiscovererTest extends ContainerTestCase
{
    private ActivityCountMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $milestones = $this->discoverer->discover();

        $this->assertTrue($milestones->isEmpty());
    }

    public function testDiscoverFirstThreshold(): void
    {
        $this->insertActivities(10);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::ACTIVITY_COUNT, $milestone->getCategory());
        $this->assertEquals('10 activities', $milestone->getTitle());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(ActivityCountContext::class, $context);
        $this->assertEquals(10, $context->getThreshold());
        $this->assertEquals(10, $context->getTotalCount());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        $this->insertActivities(50);

        $milestones = $this->discoverer->discover();

        $this->assertCount(3, $milestones);

        $titles = array_map(fn ($m) => $m->getTitle(), $milestones->toArray());
        $this->assertEquals(['10 activities', '25 activities', '50 activities'], $titles);

        $thirdMilestone = $milestones->toArray()[2];
        $this->assertNotNull($thirdMilestone->getPrevious());
        $this->assertEquals('25', $thirdMilestone->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroDistanceActivities(): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStartDateTime(SerializableDateTime::fromString(sprintf('2024-01-%02d', $i)))
                    ->build(), []
            ));
        }

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);
        $this->assertNotNull($milestones->toArray()[0]->getSportType());
    }

    public function testFunComparisonIsSet(): void
    {
        $this->insertActivities(50);

        $milestones = $this->discoverer->discover();

        $milestone50 = $milestones->toArray()[2];
        $this->assertNotNull($milestone50->getFunComparison());
    }

    public function testFunComparisonIsNullForSmallThreshold(): void
    {
        $this->insertActivities(10);

        $milestones = $this->discoverer->discover();

        $this->assertNull($milestones->toArray()[0]->getFunComparison());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new ActivityCountMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
    }

    private function insertActivities(int $count): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStartDateTime(SerializableDateTime::fromString(sprintf('2024-01-%02d', min($i, 28))))
                    ->withDistance(Kilometer::from(10))
                    ->build(), []
            ));
        }
    }
}
