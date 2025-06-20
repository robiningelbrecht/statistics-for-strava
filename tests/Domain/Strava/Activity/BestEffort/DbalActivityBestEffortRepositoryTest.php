<?php

namespace App\Tests\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffort;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\BestEffort\DbalActivityBestEffortRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityBestEffortRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ActivityBestEffortRepository $activityBestEffortRepository;

    public function testAdd(): void
    {
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(1000))
                ->withTimeInSeconds(3600)
                ->build()
        );

        $this->assertMatchesJsonSnapshot(Json::encode(
            $this->getConnection()->executeQuery('SELECT * FROM ActivityBestEffort')->fetchAllAssociative()
        ));
    }

    public function testFindBestEffortsFor(): void
    {
        /** @var SportType $sportType */
        foreach ([SportType::RIDE, SportType::GRAVEL_RIDE, SportType::RUN] as $sportType) {
            foreach ($sportType->getActivityType()->getDistancesForBestEffortCalculation() as $distance) {
                for ($i = 10; $i > 0; --$i) {
                    $this->activityBestEffortRepository->add(
                        ActivityBestEffortBuilder::fromDefaults()
                            ->withActivityId(ActivityId::fromUnprefixed($sportType->value.'-'.$distance->toMeter()->toInt().'-'.$i))
                            ->withSportType($sportType)
                            ->withDistanceInMeter($distance->toMeter())
                            ->withTimeInSeconds($i)
                            ->build()
                    );
                }
            }
        }

        foreach ([ActivityType::RIDE, ActivityType::RUN] as $activityType) {
            $this->assertMatchesJsonSnapshot(Json::encode(
                $this->activityBestEffortRepository->findBestEffortsFor($activityType)->map(
                    fn (ActivityBestEffort $bestEffort) => [
                        'activityId' => $bestEffort->getActivityId(),
                        'sportType' => $bestEffort->getSportType()->value,
                        'distanceInMeter' => $bestEffort->getDistanceInMeter()->toInt(),
                        'timeInSeconds' => $bestEffort->getTimeInSeconds(),
                    ]
                )
            ));
        }
    }

    public function testFindBestEffortsForEmpty(): void
    {
        $this->assertEmpty($this->activityBestEffortRepository->findBestEffortsFor(ActivityType::RUN));
    }

    public function testFindActivityIdsThatNeedBestEffortsCalculation(): void
    {
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                    ->build(),
                []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-4'))
                    ->build(),
                []
            )
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-4'))
                ->withStreamType(StreamType::DISTANCE)
                ->withData([1, 10000])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-4'))
                ->withStreamType(StreamType::TIME)
                ->withData([1, 2, 3, 4, 5])
                ->build()
        );

        $this->assertEquals(
            ActivityIds::fromArray([ActivityId::fromUnprefixed('test-4')]),
            $this->activityBestEffortRepository->findActivityIdsThatNeedBestEffortsCalculation()
        );
    }

    public function testDeleteForActivity(): void
    {
        $this->activityBestEffortRepository->add(ActivityBestEffortBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(10000))
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->build());

        $this->activityBestEffortRepository->add(ActivityBestEffortBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(1000))
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->build());

        $this->activityBestEffortRepository->add(ActivityBestEffortBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(000))
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->build());

        $this->activityBestEffortRepository->add(ActivityBestEffortBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(10000))
            ->withActivityId(ActivityId::fromUnprefixed('test2'))
            ->build());

        $this->activityBestEffortRepository->deleteForActivity(ActivityId::fromUnprefixed('test'));

        $this->assertEquals(
            1,
            $this->getConnection()->executeQuery('SELECT COUNT(*) FROM ActivityBestEffort')->fetchOne()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityBestEffortRepository = new DbalActivityBestEffortRepository(
            $this->getConnection(),
            $this->getContainer()->get(ActivityRepository::class)
        );
    }
}
