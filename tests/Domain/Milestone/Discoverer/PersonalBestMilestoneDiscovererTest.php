<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\PersonalBestContext;
use App\Domain\Milestone\Discoverer\PersonalBestMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
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

    public function testDiscoverCreatesPersonalBestsForFirstActivity(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->withSportType(SportType::RIDE)
                ->withDistance(Kilometer::from(50.0))
                ->withElevation(Meter::from(300.0))
                ->withMovingTimeInSeconds(7200)
                ->withAverageSpeed(KmPerHour::from(25.0))
                ->build(), []
        ));

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $this->assertGreaterThanOrEqual(4, count($milestones));

        foreach ($milestones->toArray() as $milestone) {
            $this->assertEquals(MilestoneCategory::PERSONAL_BEST, $milestone->getCategory());
            $this->assertEquals(SportType::RIDE, $milestone->getSportType());
            $this->assertNotNull($milestone->getActivityId());

            $context = $milestone->getContext();
            $this->assertInstanceOf(PersonalBestContext::class, $context);
            $this->assertNull($context->previousValue);
        }
    }

    public function testDiscoverTracksImprovements(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->withSportType(SportType::RIDE)
                ->withDistance(Kilometer::from(50.0))
                ->withElevation(Meter::from(300.0))
                ->withMovingTimeInSeconds(7200)
                ->withAverageSpeed(KmPerHour::from(25.0))
                ->build(), []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-02'))
                ->withSportType(SportType::RIDE)
                ->withDistance(Kilometer::from(80.0))
                ->withElevation(Meter::from(500.0))
                ->withMovingTimeInSeconds(10800)
                ->withAverageSpeed(KmPerHour::from(30.0))
                ->build(), []
        ));

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $improvementMilestones = array_filter(
            $milestones->toArray(),
            fn ($m) => $m->getContext() instanceof PersonalBestContext && null !== $m->getContext()->previousValue
        );

        $this->assertGreaterThanOrEqual(4, count($improvementMilestones));
    }

    public function testDiscoverTracksSportTypesSeparately(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->withSportType(SportType::RIDE)
                ->withDistance(Kilometer::from(50.0))
                ->withElevation(Meter::from(300.0))
                ->withMovingTimeInSeconds(7200)
                ->withAverageSpeed(KmPerHour::from(25.0))
                ->build(), []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-02'))
                ->withSportType(SportType::RUN)
                ->withDistance(Kilometer::from(10.0))
                ->withElevation(Meter::from(50.0))
                ->withMovingTimeInSeconds(3600)
                ->withAverageSpeed(KmPerHour::from(10.0))
                ->build(), []
        ));

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        $rideMilestones = array_filter($milestones->toArray(), fn ($m) => SportType::RIDE === $m->getSportType());
        $runMilestones = array_filter($milestones->toArray(), fn ($m) => SportType::RUN === $m->getSportType());

        $this->assertGreaterThanOrEqual(4, count($rideMilestones));
        $this->assertGreaterThanOrEqual(4, count($runMilestones));
    }

    public function testDiscoverDoesNotCreateMilestoneForNonImprovement(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->withSportType(SportType::RIDE)
                ->withDistance(Kilometer::from(50.0))
                ->withElevation(Meter::from(300.0))
                ->withMovingTimeInSeconds(7200)
                ->withAverageSpeed(KmPerHour::from(25.0))
                ->build(), []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-02'))
                ->withSportType(SportType::RIDE)
                ->withDistance(Kilometer::from(30.0))
                ->withElevation(Meter::from(100.0))
                ->withMovingTimeInSeconds(3600)
                ->withAverageSpeed(KmPerHour::from(20.0))
                ->build(), []
        ));

        $discoverer = new PersonalBestMilestoneDiscoverer($this->getConnection());
        $milestones = $discoverer->discover();

        // Only the 4 initial PBs from the first activity, no improvements
        $this->assertCount(4, $milestones);
    }
}
