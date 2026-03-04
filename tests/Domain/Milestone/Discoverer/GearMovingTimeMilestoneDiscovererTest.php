<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Milestone\Context\GearMovingTimeContext;
use App\Domain\Milestone\Discoverer\GearMovingTimeMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class GearMovingTimeMilestoneDiscovererTest extends ContainerTestCase
{
    private GearMovingTimeMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverWithNoGear(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->withMovingTimeInSeconds(100000)
                ->build(), []
        ));

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstThreshold(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 86400);

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $milestone = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::GEAR_MOVING_TIME, $milestone->getCategory());
        $this->assertNull($milestone->getSportType());
        $this->assertNull($milestone->getPrevious());

        $context = $milestone->getContext();
        $this->assertInstanceOf(GearMovingTimeContext::class, $context);
        $this->assertEquals('Canyon Endurace', $context->getGearName());
        $this->assertEquals(24.0, $context->getThreshold()->toFloat());
    }

    public function testDiscoverMultipleThresholdsWithPreviousChain(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 100000);
        $this->insertActivity('2', '2024-01-02', $gearId, 80000);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $milestonesArray = $milestones->toArray();
        $this->assertNull($milestonesArray[0]->getPrevious());
        $this->assertNotNull($milestonesArray[1]->getPrevious());
        $this->assertEquals(Hour::from(24), $milestonesArray[1]->getPrevious()->getThreshold());
    }

    public function testDiscoverTracksGearsSeparately(): void
    {
        $bikeId = GearId::fromUnprefixed('bike-1');
        $shoesId = GearId::fromUnprefixed('shoes-1');
        $this->insertGear($bikeId, 'Canyon Endurace');
        $this->insertGear($shoesId, 'Nike Pegasus');

        $this->insertActivity('1', '2024-01-01', $bikeId, 86400);
        $this->insertActivity('2', '2024-01-02', $shoesId, 86400);

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);
    }

    public function testDiscoverSkipsZeroMovingTime(): void
    {
        $gearId = GearId::fromUnprefixed('bike-1');
        $this->insertGear($gearId, 'Canyon Endurace');
        $this->insertActivity('1', '2024-01-01', $gearId, 0);

        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new GearMovingTimeMilestoneDiscoverer(
            $this->getConnection(),
            new IncrementingMilestoneIdFactory(),
        );
    }

    private function insertGear(GearId $gearId, string $name): void
    {
        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId($gearId)
                ->withName($name)
                ->build()
        );
    }

    private function insertActivity(string $id, string $date, GearId $gearId, int $movingTimeInSeconds): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->withGearId($gearId)
                ->build(), []
        ));
    }
}
