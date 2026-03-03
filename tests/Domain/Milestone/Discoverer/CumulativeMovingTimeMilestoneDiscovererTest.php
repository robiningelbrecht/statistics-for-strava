<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeMovingTimeContext;
use App\Domain\Milestone\Discoverer\CumulativeMovingTimeMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class CumulativeMovingTimeMilestoneDiscovererTest extends ContainerTestCase
{
    private CumulativeMovingTimeMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstThreshold(): void
    {
        // 24 hours = 86400 seconds
        $this->insertActivity(1, '2024-01-01', 86400);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $globalMilestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::CUMULATIVE_MOVING_TIME, $globalMilestone->getCategory());
        $this->assertNull($globalMilestone->getSportType());
        $this->assertNull($globalMilestone->getPrevious());

        $context = $globalMilestone->getContext();
        $this->assertInstanceOf(CumulativeMovingTimeContext::class, $context);
        $this->assertEquals(24.0, $context->getThreshold()->toFloat());

        $sportMilestone = $milestones->toArray()[1];
        $this->assertEquals(SportType::RIDE, $sportMilestone->getSportType());
        $this->assertNull($sportMilestone->getPrevious());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        // 48h = 172800s
        $this->insertActivity(1, '2024-01-01', 100000);
        $this->insertActivity(2, '2024-01-02', 80000);

        $milestones = $this->discoverer->discover();

        $this->assertCount(4, $milestones);

        $global48 = $milestones->toArray()[2];
        $this->assertNull($global48->getSportType());
        $this->assertNotNull($global48->getPrevious());
        $this->assertEquals('24 h', $global48->getPrevious()->getLabel());

        $sport48 = $milestones->toArray()[3];
        $this->assertEquals(SportType::RIDE, $sport48->getSportType());
        $this->assertNotNull($sport48->getPrevious());
        $this->assertEquals('24 h', $sport48->getPrevious()->getLabel());
    }

    public function testDiscoverSkipsZeroMovingTime(): void
    {
        $this->insertActivity(1, '2024-01-01', 0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithMultipleSportTypes(): void
    {
        // 24h each from different sports = 48h global
        $this->insertActivityWithSportType(1, '2024-01-01', 86400, SportType::RIDE);
        $this->insertActivityWithSportType(2, '2024-01-02', 86400, SportType::RUN);

        $milestones = $this->discoverer->discover();

        $milestonesArray = $milestones->toArray();

        $this->assertNull($milestonesArray[0]->getSportType());

        $this->assertEquals(SportType::RIDE, $milestonesArray[1]->getSportType());

        $this->assertNull($milestonesArray[2]->getSportType());
        $this->assertNotNull($milestonesArray[2]->getPrevious());
        $this->assertEquals('24 h', $milestonesArray[2]->getPrevious()->getLabel());

        $this->assertEquals(SportType::RUN, $milestonesArray[3]->getSportType());
    }

    public function testFunComparisonIsNullForSmallThreshold(): void
    {
        $this->insertActivity(1, '2024-01-01', 86400);

        $milestones = $this->discoverer->discover();

        $this->assertNotNull($milestones->toArray()[0]->getFunComparison());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new CumulativeMovingTimeMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
    }

    private function insertActivity(int $id, string $date, int $movingTimeInSeconds): void
    {
        $this->insertActivityWithSportType($id, $date, $movingTimeInSeconds, SportType::RIDE);
    }

    private function insertActivityWithSportType(int $id, string $date, int $movingTimeInSeconds, SportType $sportType): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->withSportType($sportType)
                ->build(), []
        ));
    }
}
