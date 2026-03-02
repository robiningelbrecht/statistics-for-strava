<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityRecordContext;
use App\Domain\Milestone\Discoverer\ActivityDistanceMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class ActivityDistanceMilestoneDiscovererTest extends ContainerTestCase
{
    private ActivityDistanceMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverCreatesPersonalBestForFirstActivity(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 50.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::ACTIVITY_DISTANCE, $milestone->getCategory());
        $this->assertEquals('Longest distance', $milestone->getTitle());
        $this->assertEquals(SportType::RIDE, $milestone->getSportType());
        $this->assertNotNull($milestone->getActivityId());

        $context = $milestone->getContext();
        $this->assertInstanceOf(ActivityRecordContext::class, $context);
        $this->assertInstanceOf(Kilometer::class, $context->getValue());
        $this->assertEquals(50.0, $context->getValue()->toFloat());
        $this->assertNull($milestone->getPrevious());
    }

    public function testDiscoverTracksImprovements(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 50.0);
        $this->insertActivity(2, '2024-01-02', SportType::RIDE, 80.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $second = $milestones->toArray()[1];
        $context = $second->getContext();
        $this->assertInstanceOf(ActivityRecordContext::class, $context);
        $this->assertEquals(80.0, $context->getValue()->toFloat());
        $this->assertNotNull($second->getPrevious());
        $this->assertEquals('2024-01-01', $second->getPrevious()->getAchievedOn()->format('Y-m-d'));
    }

    public function testDiscoverDoesNotCreateMilestoneForNonImprovement(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 50.0);
        $this->insertActivity(2, '2024-01-02', SportType::RIDE, 30.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);
    }

    public function testDiscoverTracksSportTypesSeparately(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 50.0);
        $this->insertActivity(2, '2024-01-02', SportType::RUN, 10.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);
        $this->assertEquals(SportType::RIDE, $milestones->toArray()[0]->getSportType());
        $this->assertEquals(SportType::RUN, $milestones->toArray()[1]->getSportType());
    }

    public function testDiscoverSkipsZeroDistance(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 0.0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new ActivityDistanceMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
    }

    private function insertActivity(int $id, string $date, SportType $sportType, float $distanceKm): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withSportType($sportType)
                ->withDistance(Kilometer::from($distanceKm))
                ->build(), []
        ));
    }
}
