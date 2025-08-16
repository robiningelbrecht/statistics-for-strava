<?php

namespace App\Tests\Domain\Rewind\FindElevationPerMonth;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Gear\GearId;
use App\Domain\Rewind\FindElevationPerMonth\FindElevationPerMonth;
use App\Domain\Rewind\FindElevationPerMonth\FindElevationPerMonthQueryHandler;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class FindElevationPerMonthQueryHandlerTest extends ContainerTestCase
{
    private FindElevationPerMonthQueryHandler $queryHandler;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('0'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->withElevation(Meter::from(10))
                ->build(),
            []
        ));

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
                ->withGearId(GearId::fromUnprefixed('3'))
                ->withStartDateTime(SerializableDateTime::fromString('2023-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed('2'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->withElevation(Meter::from(20))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->withElevation(Meter::from(10))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('8'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->withElevation(Meter::from(10))
                ->build(),
            []
        ));

        /** @var \App\Domain\Rewind\FindElevationPerMonth\FindElevationPerMonthResponse $response */
        $response = $this->queryHandler->handle(new FindElevationPerMonth(Years::fromArray([Year::fromInt(2024)])));
        $this->assertEquals(
            [
                [1, SportType::RIDE, Meter::from(40)],
                [3, SportType::RIDE, Meter::from(10)],
            ],
            $response->getElevationPerMonth(),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindElevationPerMonthQueryHandler($this->getConnection());
    }
}
