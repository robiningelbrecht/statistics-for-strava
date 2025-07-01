<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ActivityTypes;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\SportType\DbalSportTypeRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypesSortingOrder;
use App\Domain\Strava\Activity\SportTypeBasedActivityTypeRepository;
use App\Tests\ContainerTestCase;

class SportTypeBasedActivityTypeRepositoryTest extends ContainerTestCase
{
    public function testFindAll(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RUN)
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RUN)
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->build(),
            []
        ));

        $activityTypeRepository = new SportTypeBasedActivityTypeRepository(
            new DbalSportTypeRepository(
                $this->getConnection(),
                SportTypesSortingOrder::fromArray([SportType::RUN, SportType::WALK])
            )
        );

        $this->assertEquals(
            ActivityTypes::fromArray([ActivityType::RUN, ActivityType::WALK]),
            $activityTypeRepository->findAll(),
        );

        $activityTypeRepository = new SportTypeBasedActivityTypeRepository(
            new DbalSportTypeRepository(
                $this->getConnection(),
                SportTypesSortingOrder::fromArray([SportType::WALK, SportType::RUN])
            )
        );

        $this->assertEquals(
            ActivityTypes::fromArray([ActivityType::WALK, ActivityType::RUN]),
            $activityTypeRepository->findAll(),
        );
    }
}
