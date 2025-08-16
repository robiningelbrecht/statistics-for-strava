<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypes;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\DbalSportTypeRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypesSortingOrder;
use App\Domain\Activity\SportTypeBasedActivityTypeRepository;
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
