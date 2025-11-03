<?php

namespace App\Tests\Domain\Activity\SportType;

use App\BuildApp\BuildPhotosHtml\HidePhotosForSportTypes;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\DbalSportTypeRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\SportType\SportTypesSortingOrder;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class DbalSportTypeRepositoryTest extends ContainerTestCase
{
    public function testFindAll(): void
    {
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

        $sportTypeRepository = new DbalSportTypeRepository(
            $this->getConnection(),
            SportTypesSortingOrder::fromArray([SportType::RUN, SportType::WALK]),
            HidePhotosForSportTypes::empty(),
        );

        $this->assertEquals(
            SportTypes::fromArray([SportType::RUN, SportType::WALK]),
            $sportTypeRepository->findAll(),
        );

        $sportTypeRepository = new DbalSportTypeRepository(
            $this->getConnection(),
            SportTypesSortingOrder::fromArray([SportType::WALK, SportType::RUN]),
            HidePhotosForSportTypes::empty()
        );

        $this->assertEquals(
            SportTypes::fromArray([SportType::WALK, SportType::RUN]),
            $sportTypeRepository->findAll(),
        );
    }

    public function testFindForImages(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RUN)
                ->withTotalImageCount(3)
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RUN)
                ->withTotalImageCount(3)
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->withTotalImageCount(0)
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RIDE)
                ->withTotalImageCount(3)
                ->build(),
            []
        ));

        $sportTypeRepository = new DbalSportTypeRepository(
            $this->getConnection(),
            SportTypesSortingOrder::fromArray([SportType::RUN, SportType::WALK]),
            HidePhotosForSportTypes::fromArray([SportType::RIDE])
        );

        $this->assertEquals(
            SportTypes::fromArray([SportType::RUN]),
            $sportTypeRepository->findForImages(),
        );
    }
}
