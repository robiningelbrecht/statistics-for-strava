<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\BestEffort\ActivityBestEffort;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\PersonalBestContext;
use App\Domain\Milestone\Discoverer\PersonalBestMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class PersonalBestMilestoneDiscovererTest extends ContainerTestCase
{
    public function testDiscoverWithNoActivities(): void
    {
        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());

        $this->assertTrue($discoverer->discover()->isEmpty());
    }

    public function testDiscoverCreatesPersonalBestForFirstBestEffort(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RUN);
        $this->insertBestEffort(1, SportType::RUN, 5000, 1200);

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::PERSONAL_BEST, $milestone->getCategory());
        $this->assertEquals('5km', $milestone->getTitle());
        $this->assertEquals(SportType::RUN, $milestone->getSportType());
        $this->assertNotNull($milestone->getActivityId());

        $context = $milestone->getContext();
        $this->assertInstanceOf(PersonalBestContext::class, $context);
        $this->assertInstanceOf(Kilometer::class, $context->getDistance());
        $this->assertEquals(5.0, $context->getDistance()->toFloat());
        $this->assertInstanceOf(Seconds::class, $context->getTime());
        $this->assertEquals(1200, $context->getTime()->toInt());
        $this->assertNull($milestone->getPrevious());
    }

    public function testDiscoverTracksImprovements(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RUN);
        $this->insertBestEffort(1, SportType::RUN, 5000, 1200);

        $this->insertActivity(2, '2024-01-02', SportType::RUN);
        $this->insertBestEffort(2, SportType::RUN, 5000, 1100);

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(2, $milestones);

        $second = $milestones->toArray()[1];
        $context = $second->getContext();
        $this->assertInstanceOf(PersonalBestContext::class, $context);
        $this->assertEquals(1100, $context->getTime()->toInt());
        $this->assertNotNull($second->getPrevious());
        $this->assertEquals('2024-01-01', $second->getPrevious()->getAchievedOn()->format('Y-m-d'));
    }

    public function testDiscoverDoesNotCreateMilestoneForSlowerTime(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RUN);
        $this->insertBestEffort(1, SportType::RUN, 5000, 1200);

        $this->insertActivity(2, '2024-01-02', SportType::RUN);
        $this->insertBestEffort(2, SportType::RUN, 5000, 1500);

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(1, $milestones);
    }

    public function testDiscoverTracksSportTypesSeparately(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RUN);
        $this->insertBestEffort(1, SportType::RUN, 5000, 1200);

        $this->insertActivity(2, '2024-01-02', SportType::RIDE);
        $this->insertBestEffort(2, SportType::RIDE, 10000, 1800);

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(2, $milestones);
        $this->assertEquals(SportType::RUN, $milestones->toArray()[0]->getSportType());
        $this->assertEquals(SportType::RIDE, $milestones->toArray()[1]->getSportType());
    }

    public function testDiscoverTracksDistancesSeparately(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RUN);
        $this->insertBestEffort(1, SportType::RUN, 5000, 1200);
        $this->insertBestEffort(1, SportType::RUN, 10000, 2700);

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertCount(2, $milestones);

        $titles = array_map(fn ($m) => $m->getTitle(), $milestones->toArray());
        $this->assertEquals(['5km', '10km'], $titles);
    }

    public function testDiscoverIgnoresUnknownDistances(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RUN);
        $this->insertBestEffort(1, SportType::RUN, 7777, 2000);

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertTrue($milestones->isEmpty());
    }

    private function insertActivity(int $id, string $date, SportType $sportType): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withSportType($sportType)
                ->build(), []
        ));
    }

    private function insertBestEffort(int $activityId, SportType $sportType, int $distanceInMeter, int $timeInSeconds): void
    {
        $this->getContainer()->get(ActivityBestEffortRepository::class)->add(
            ActivityBestEffort::create(
                activityId: ActivityId::fromUnprefixed($activityId),
                distanceInMeter: Meter::from($distanceInMeter),
                sportType: $sportType,
                timeInSeconds: $timeInSeconds,
            )
        );
    }
}
