<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityRecordContext;
use App\Domain\Milestone\Discoverer\ActivityElevationMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class ActivityElevationMilestoneDiscovererTest extends ContainerTestCase
{
    public function testDiscoverWithNoActivities(): void
    {
        $discoverer = new ActivityElevationMilestoneDiscoverer($this->getConnection());

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverCreatesPersonalBestForFirstActivity(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 300.0);

        $discoverer = new ActivityElevationMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::ACTIVITY_ELEVATION, $milestone->getCategory());
        $this->assertEquals('Most elevation', $milestone->getTitle());
        $this->assertEquals(SportType::RIDE, $milestone->getSportType());
        $this->assertNotNull($milestone->getActivityId());

        $context = $milestone->getContext();
        $this->assertInstanceOf(ActivityRecordContext::class, $context);
        $this->assertInstanceOf(Meter::class, $context->getValue());
        $this->assertEquals(300.0, $context->getValue()->toFloat());
        $this->assertNull($context->getPreviousValue());
    }

    public function testDiscoverTracksImprovements(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 300.0);
        $this->insertActivity(2, '2024-01-02', SportType::RIDE, 500.0);

        $discoverer = new ActivityElevationMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(2, $milestones);

        $second = $milestones->toArray()[1];
        $context = $second->getContext();
        $this->assertInstanceOf(ActivityRecordContext::class, $context);
        $this->assertEquals(500.0, $context->getValue()->toFloat());
        $this->assertNotNull($context->getPreviousValue());
        $this->assertEquals(300.0, $context->getPreviousValue()->toFloat());
    }

    public function testDiscoverDoesNotCreateMilestoneForNonImprovement(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 500.0);
        $this->insertActivity(2, '2024-01-02', SportType::RIDE, 200.0);

        $discoverer = new ActivityElevationMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);
    }

    public function testDiscoverTracksSportTypesSeparately(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 300.0);
        $this->insertActivity(2, '2024-01-02', SportType::RUN, 50.0);

        $discoverer = new ActivityElevationMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(2, $milestones);
        $this->assertEquals(SportType::RIDE, $milestones->toArray()[0]->getSportType());
        $this->assertEquals(SportType::RUN, $milestones->toArray()[1]->getSportType());
    }

    public function testDiscoverSkipsZeroElevation(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 0.0);

        $discoverer = new ActivityElevationMilestoneDiscoverer($this->getConnection());

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    private function insertActivity(int $id, string $date, SportType $sportType, float $elevationM): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withSportType($sportType)
                ->withElevation(Meter::from($elevationM))
                ->build(), []
        ));
    }
}
