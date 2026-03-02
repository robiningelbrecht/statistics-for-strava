<?php

namespace App\Tests\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\FirstContext;
use App\Domain\Milestone\Discoverer\FirstsMilestoneDiscoverer;
use App\Domain\Milestone\MilestoneCategory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Milestone\IncrementingMilestoneIdFactory;

class FirstsMilestoneDiscovererTest extends ContainerTestCase
{
    private FirstsMilestoneDiscoverer $discoverer;

    public function testDiscoverWithNoActivities(): void
    {
        $this->assertTrue($this->discoverer->discover()->isEmpty());
    }

    public function testDiscoverFirstOfEachSportType(): void
    {
        $this->insertActivity(1, '2024-01-01', SportType::RIDE, 'Morning ride');
        $this->insertActivity(2, '2024-01-02', SportType::RUN, 'Evening run');
        $this->insertActivity(3, '2024-01-03', SportType::RIDE, 'Another ride');

        $milestones = $this->discoverer->discover();

        $this->assertCount(2, $milestones);

        $first = $milestones->toArray()[0];
        $this->assertEquals(MilestoneCategory::FIRST, $first->getCategory());
        $this->assertEquals(SportType::RIDE, $first->getSportType());
        $this->assertNotNull($first->getActivityId());
        $this->assertNull($first->getPrevious());
        $this->assertNull($first->getFunComparison());

        $context = $first->getContext();
        $this->assertInstanceOf(FirstContext::class, $context);
        $this->assertEquals(SportType::RIDE, $context->getSportType());
        $this->assertEquals('Morning ride', $context->getActivityName());

        $second = $milestones->toArray()[1];
        $this->assertEquals(SportType::RUN, $second->getSportType());
    }

    public function testDiscoverRespectsChronologicalOrder(): void
    {
        $this->insertActivity(1, '2024-01-02', SportType::RIDE, 'Second ride');
        $this->insertActivity(2, '2024-01-01', SportType::RIDE, 'First ride');

        $milestones = $this->discoverer->discover();

        $this->assertCount(1, $milestones);

        $context = $milestones->toArray()[0]->getContext();
        $this->assertInstanceOf(FirstContext::class, $context);
        $this->assertEquals('First ride', $context->getActivityName());
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new FirstsMilestoneDiscoverer($this->getConnection(), new IncrementingMilestoneIdFactory());
    }

    private function insertActivity(int $id, string $date, SportType $sportType, string $name): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed($id))
                ->withStartDateTime(SerializableDateTime::fromString($date))
                ->withSportType($sportType)
                ->withName($name)
                ->build(), []
        ));
    }
}
