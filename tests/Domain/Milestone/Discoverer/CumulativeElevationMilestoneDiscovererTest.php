<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeElevationContext;
use App\Domain\Milestone\Discoverer\CumulativeElevationMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class CumulativeElevationMilestoneDiscovererTest extends ContainerTestCase
{
    private CumulativeElevationMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstMetricThreshold(): void
    {
        $this->insertActivity(1, '2024-01-01', 500.0);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $globalMilestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::CUMULATIVE_ELEVATION, $globalMilestone->getCategory());
        $this->assertNull($globalMilestone->getSportType());
        $this->assertNull($globalMilestone->getPrevious());

        $context = $globalMilestone->getContext();
        $this->assertInstanceOf(CumulativeElevationContext::class, $context);
        $this->assertInstanceOf(Meter::class, $context->getThreshold());
        $this->assertEquals(500.0, $context->getThreshold()->toFloat());

        $sportMilestone = $milestones->toArray()[1];
        $this->assertEquals(SportType::RIDE, $sportMilestone->getSportType());
        $this->assertNull($sportMilestone->getPrevious());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        $this->insertActivity(1, '2024-01-01', 600.0);
        $this->insertActivity(2, '2024-01-02', 500.0);

        $milestones = $this->discoverer->discover();

        $global1000 = $milestones->toArray()[2];
        $this->assertNull($global1000->getSportType());
        $this->assertNotNull($global1000->getPrevious());
        $this->assertEquals('500 m', $global1000->getPrevious()->getLabel());

        $sport1000 = $milestones->toArray()[3];
        $this->assertEquals(SportType::RIDE, $sport1000->getSportType());
        $this->assertNotNull($sport1000->getPrevious());
        $this->assertEquals('500 m', $sport1000->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroElevation(): void
    {
        $this->insertActivity(1, '2024-01-01', 0.0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithImperialUnits(): void
    {
        $this->insertActivity(1, '2024-01-01', 500.0);

        $discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::IMPERIAL,
            new IncrementingMilestoneIdFactory(),
        );
        $milestones = $discoverer->discover();

        $this->assertGreaterThanOrEqual(2, count($milestones));
    }

    public function testDiscoverWithMultipleSportTypes(): void
    {
        $this->insertActivityWithSportType(1, '2024-01-01', 600.0, SportType::RIDE);
        $this->insertActivityWithSportType(2, '2024-01-02', 500.0, SportType::RUN);

        $milestones = $this->discoverer->discover();

        $milestonesArray = $milestones->toArray();

        $this->assertNull($milestonesArray[0]->getSportType());

        $this->assertEquals(SportType::RIDE, $milestonesArray[1]->getSportType());

        $this->assertNull($milestonesArray[2]->getSportType());

        $this->assertEquals(SportType::RUN, $milestonesArray[3]->getSportType());
    }

    public function testFunComparisonIsSet(): void
    {
        $this->insertActivity(1, '2024-01-01', 1000.0);

        $milestones = $this->discoverer->discover();

        $last = $milestones->toArray()[count($milestones) - 1];
        $this->assertNotNull($last->getFunComparison());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new CumulativeElevationMilestoneDiscoverer(
            $this->getConnection(),
            UnitSystem::METRIC,
            new IncrementingMilestoneIdFactory(),
        );
    }

    private function insertActivity(int $id, string $date, float $elevationM): void
    {
        $this->insertActivityWithSportType($id, $date, $elevationM, SportType::RIDE);
    }

    private function insertActivityWithSportType(int $id, string $date, float $elevationM, SportType $sportType): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withElevation(Meter::from($elevationM))
                ->withSportType($sportType)
                ->build(), []
        ));
    }
}
