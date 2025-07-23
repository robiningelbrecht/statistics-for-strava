<?php

namespace App\Tests\Domain\Strava\Rewind\FindMovingTimePerSportType;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Rewind\FindMovingTimePerSportType\FindMovingTimePerSportType;
use App\Domain\Strava\Rewind\FindMovingTimePerSportType\FindMovingTimePerSportTypeQueryHandler;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class FindMovingTimePerSportTypeQueryHandlerTest extends ContainerTestCase
{
    private FindMovingTimePerSportTypeQueryHandler $queryHandler;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withSportType(SportType::RIDE)
                ->withGearId(GearId::fromUnprefixed('3'))
                ->withStartDateTime(SerializableDateTime::fromString('2023-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed('2'))
                ->withSportType(SportType::RIDE)
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withSportType(SportType::RIDE)
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('8'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withSportType(SportType::VIRTUAL_RIDE)
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));

        /** @var \App\Domain\Strava\Rewind\FindMovingTimePerSportType\FindMovingTimePerSportTypeResponse $response */
        $response = $this->queryHandler->handle(new FindMovingTimePerSportType(Years::fromArray([Year::fromInt(2024)])));
        $this->assertEquals(
            [
                'Ride' => 20,
                'VirtualRide' => 10,
            ],
            $response->getMovingTimePerSportType()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindMovingTimePerSportTypeQueryHandler($this->getConnection());
    }
}
