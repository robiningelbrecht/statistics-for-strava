<?php

namespace App\Tests\Domain\Milestone;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\Eddington\EddingtonCalculator;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Discoverer\ActivityCountMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\CumulativeDistanceMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\CumulativeElevationMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\CumulativeMovingTimeMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\EddingtonMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\FirstsMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\PersonalBestMilestoneDiscoverer;
use App\Domain\Milestone\Discoverer\StreakMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCollector;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class MilestoneCollectorTest extends ContainerTestCase
{
    public function testDiscoverAllWithNoActivities(): void
    {
        $collector = $this->createCollector();

        $this->assertTrue($collector->discoverAll()->isEmpty());
    }

    public function testDiscoverAllAggregatesFromAllDiscoverers(): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStartDateTime(SerializableDateTime::fromString(sprintf('2024-01-%02d', $i)))
                    ->withSportType(SportType::RIDE)
                    ->withDistance(Kilometer::from(20.0))
                    ->withElevation(Meter::from(100.0))
                    ->withMovingTimeInSeconds(3600)
                    ->withAverageSpeed(KmPerHour::from(20.0))
                    ->build(), []
            ));
        }

        $collector = $this->createCollector();
        $milestones = $collector->discoverAll();

        $this->assertFalse($milestones->isEmpty());

        $categories = array_unique(array_map(
            fn ($m) => $m->getCategory()->value,
            $milestones->toArray()
        ));

        $this->assertContains('first', $categories);
        $this->assertContains('activityCount', $categories);
        $this->assertContains('personalBest', $categories);
    }

    private function createCollector(): MilestoneCollector
    {
        $connection = $this->getConnection();
        $unitSystem = UnitSystem::METRIC;

        return new MilestoneCollector([
            new ActivityCountMilestoneDiscoverer($connection),
            new CumulativeDistanceMilestoneDiscoverer($connection, $unitSystem),
            new CumulativeElevationMilestoneDiscoverer($connection, $unitSystem),
            new CumulativeMovingTimeMilestoneDiscoverer($connection),
            new EddingtonMilestoneDiscoverer(
                $this->getContainer()->get(EddingtonCalculator::class),
                $unitSystem,
            ),
            new FirstsMilestoneDiscoverer($connection),
            new PersonalBestMilestoneDiscoverer($connection),
            new StreakMilestoneDiscoverer($connection),
        ]);
    }
}
