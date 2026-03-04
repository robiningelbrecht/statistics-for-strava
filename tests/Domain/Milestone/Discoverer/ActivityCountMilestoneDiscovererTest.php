<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
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

        $this->assertCount(2, $milestones);

        $globalMilestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::ACTIVITY_COUNT, $globalMilestone->getCategory());
        $this->assertNull($globalMilestone->getSportType());
        $this->assertNull($globalMilestone->getPrevious());

        $context = $globalMilestone->getContext();
        $this->assertInstanceOf(ActivityCountContext::class, $context);
        $this->assertEquals(10, $context->getThreshold());

        $sportMilestone = $milestones->toArray()[1];
        $this->assertEquals(MilestoneCategory::ACTIVITY_COUNT, $sportMilestone->getCategory());
        $this->assertEquals(SportType::RIDE, $sportMilestone->getSportType());
        $this->assertNull($sportMilestone->getPrevious());
    }

    public function testDiscoverMultipleThresholds(): void
    {
        $this->insertActivities(50);

        $milestones = $this->discoverer->discover();

        $this->assertCount(6, $milestones);

        $global50 = $milestones->toArray()[4];
        $this->assertNull($global50->getSportType());
        $this->assertNotNull($global50->getPrevious());
        $this->assertEquals('25', $global50->getPrevious()->getThreshold());

        $sport50 = $milestones->toArray()[5];
        $this->assertEquals(SportType::RIDE, $sport50->getSportType());
        $this->assertNotNull($sport50->getPrevious());
        $this->assertEquals('25', $sport50->getPrevious()->getThreshold());
    }

    public function testDiscoverWithMultipleSportTypes(): void
    {
        for ($i = 1; $i <= 15; ++$i) {
            $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStartDateTime(SerializableDateTime::fromString(sprintf('2024-01-%02d', $i)))
                    ->withSportType(SportType::RIDE)
                    ->withDistance(Kilometer::from(10))
                    ->build(), []
            ));
        }
        for ($i = 16; $i <= 25; ++$i) {
            $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStartDateTime(SerializableDateTime::fromString(sprintf('2024-01-%02d', $i)))
                    ->withSportType(SportType::RUN)
                    ->withDistance(Kilometer::from(10))
                    ->build(), []
            ));
        }

        $milestones = $this->discoverer->discover();

        $this->assertCount(4, $milestones);

        $milestonesArray = $milestones->toArray();

        $this->assertNull($milestonesArray[0]->getSportType());

        $this->assertEquals(SportType::RIDE, $milestonesArray[1]->getSportType());

        $this->assertNull($milestonesArray[2]->getSportType());
        $this->assertNotNull($milestonesArray[2]->getPrevious());
        $this->assertEquals('10', $milestonesArray[2]->getPrevious()->getThreshold());

        $this->assertEquals(SportType::RUN, $milestonesArray[3]->getSportType());
    }

    public function testFunComparisonIsSet(): void
    {
        $this->insertActivities(50);

        $milestones = $this->discoverer->discover();

        $globalMilestone50 = $milestones->toArray()[4];
        $this->assertNotNull($globalMilestone50->getFunComparison());
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
