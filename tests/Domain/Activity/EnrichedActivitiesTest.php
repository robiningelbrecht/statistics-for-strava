<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class EnrichedActivitiesTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private EnrichedActivities $enrichedActivities;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activity,
            ['raw' => 'data']
        ));

        $activity = $activity->withTags([
            '#sfs-chain-lubed',
            '#sfs-chain-replaced',
            '#sfs-chain-cleaned',
            '#sfs-di-2-charged',
            '#sfs-peddle-board',
            '#sfs-workout-shoes',
        ]);

        $persisted = $this->enrichedActivities->find($activity->getId());
        $this->assertEquals(
            $activity,
            $persisted,
        );
    }

    public function testFindItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->enrichedActivities->find(ActivityId::fromUnprefixed(1));
    }

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            [$activityOne->getId(), $activityTwo->getId(), $activityThree->getId()],
            $this->enrichedActivities->findAll()->map(fn (Activity $activity): ActivityId => $activity->getId())
        );
    }

    public function testFindByStartDate(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->withSportType(SportType::BADMINTON)
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->withSportType(SportType::RUN)
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withSportType(SportType::MOUNTAIN_BIKE_RIDE)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            [$activityOne->getId(), $activityTwo->getId()],
            $this->enrichedActivities->findByStartDate(SerializableDateTime::fromString('2023-10-10'), null)->map(fn (Activity $activity): ActivityId => $activity->getId())
        );

        $this->assertEquals(
            [$activityOne->getId()],
            $this->enrichedActivities->findByStartDate(SerializableDateTime::fromString('2023-10-10'), ActivityType::RACQUET_PADDLE_SPORTS)->map(fn (Activity $activity): ActivityId => $activity->getId())
        );
    }

    public function testFindBySportTypes(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->withSportType(SportType::BADMINTON)
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->withSportType(SportType::RUN)
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withSportType(SportType::MOUNTAIN_BIKE_RIDE)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            [$activityTwo->getId(), $activityThree->getId()],
            $this->enrichedActivities->findBySportTypes(SportTypes::fromArray([SportType::RUN, SportType::MOUNTAIN_BIKE_RIDE]))->map(fn (Activity $activity): ActivityId => $activity->getId())
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->enrichedActivities = $this->getContainer()->get(EnrichedActivities::class);
        $this->activityWithRawDataRepository = $this->getContainer()->get(ActivityWithRawDataRepository::class);
    }
}
