<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityRecordContext;
use App\Domain\Milestone\Discoverer\ActivityMovingTimeMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class ActivityMovingTimeMilestoneDiscovererTest extends ContainerTestCase
{
    private ActivityMovingTimeMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverCreatesPersonalBestForFirstActivity(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 7200);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::ACTIVITY_MOVING_TIME, $milestone->getCategory());
        $this->assertEquals(SportType::RIDE, $milestone->getSportType());
        $this->assertNotNull($milestone->getActivityId());

        $context = $milestone->getContext();
        $this->assertInstanceOf(ActivityRecordContext::class, $context);
        $this->assertInstanceOf(Seconds::class, $context->getValue());
        $this->assertEquals(7200, $context->getValue()->toInt());
        $this->assertNull($milestone->getPrevious());
    }

    public function testDiscoverTracksImprovements(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 7200);
        $this->insertActivity(2, '2024-01-02', SportType::RIDE, 10800);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $second = $milestones->toArray()[1];
        $context = $second->getContext();
        $this->assertInstanceOf(ActivityRecordContext::class, $context);
        $this->assertEquals(10800, $context->getValue()->toInt());
        $this->assertNotNull($second->getPrevious());
        $this->assertEquals('2024-01-01', $second->getPrevious()->getAchievedOn()->format('Y-m-d'));
    }

    public function testDiscoverDoesNotCreateMilestoneForNonImprovement(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 7200);
        $this->insertActivity(2, '2024-01-02', SportType::RIDE, 3600);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);
    }

    public function testDiscoverTracksSportTypesSeparately(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 7200);
        $this->insertActivity(2, '2024-01-02', SportType::RUN, 3600);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);
        $this->assertEquals(SportType::RIDE, $milestones->toArray()[0]->getSportType());
        $this->assertEquals(SportType::RUN, $milestones->toArray()[1]->getSportType());
    }

    public function testDiscoverSkipsZeroMovingTime(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new ActivityMovingTimeMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
    }

    private function insertActivity(int $id, string $date, SportType $sportType, int $movingTimeInSeconds): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withSportType($sportType)
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->build(), []
        ));
    }
}
