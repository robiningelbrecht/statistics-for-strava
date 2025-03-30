<?php

namespace App\Tests\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CombinedStream\DbalCombinedActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;

class DbalCombinedActivityStreamRepositoryTest extends ContainerTestCase
{
    private CombinedActivityStreamRepository $combinedActivityStreamRepository;

    public function testAddAndFindOneForActivityAndUnitSystem(): void
    {
        $combinedActivityStream = CombinedActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed('test'))
            ->withUnitSystem(UnitSystem::METRIC)
            ->build();
        $this->combinedActivityStreamRepository->add($combinedActivityStream);
        $this->combinedActivityStreamRepository->add(
            CombinedActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withUnitSystem(UnitSystem::IMPERIAL)
                ->build()
        );
        $this->combinedActivityStreamRepository->add(
            CombinedActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test2'))
                ->withUnitSystem(UnitSystem::METRIC)
                ->build()
        );

        $this->assertEquals(
            $combinedActivityStream,
            $this->combinedActivityStreamRepository->findOneForActivityAndUnitSystem(
                activityId: ActivityId::fromUnprefixed('test'),
                unitSystem: UnitSystem::METRIC
            )
        );
    }

    public function testFindActivityIdsThatNeedStreamCombining(): void
    {
        $this->combinedActivityStreamRepository->add(
            CombinedActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withUnitSystem(UnitSystem::METRIC)
                ->build()
        );
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withSportType(SportType::RIDE)
                    ->withActivityId(ActivityId::fromUnprefixed('test'))
                    ->build(),
                []
            )
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withStreamType(StreamType::DISTANCE)
                ->withData([1])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([2])
                ->build()
        );

        $this->combinedActivityStreamRepository->add(
            CombinedActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withUnitSystem(UnitSystem::IMPERIAL)
                ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-3'))
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
                ->withData([])
            ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-4'))
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([])
                ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                    ->build(),
                []
            )
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::DISTANCE)
                ->withData([1])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([2])
                ->build()
        );

        $this->assertEquals(
            ActivityIds::fromArray([ActivityId::fromUnprefixed('test-5')]),
            $this->combinedActivityStreamRepository->findActivityIdsThatNeedStreamCombining()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->combinedActivityStreamRepository = new DbalCombinedActivityStreamRepository(
            $this->getConnection()
        );
    }
}
