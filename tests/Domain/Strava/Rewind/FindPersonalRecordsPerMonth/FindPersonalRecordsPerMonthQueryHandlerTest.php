<?php

namespace App\Tests\Domain\Strava\Rewind\FindPersonalRecordsPerMonth;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth\FindPersonalRecordsPerMonth;
use App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth\FindPersonalRecordsPerMonthQueryHandler;
use App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth\FindPersonalRecordsPerMonthResponse;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class FindPersonalRecordsPerMonthQueryHandlerTest extends ContainerTestCase
{
    private FindPersonalRecordsPerMonthQueryHandler $queryHandler;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('0'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->build(),
            [
                'pr_count' => 3,
            ]
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
                ->build(),
            [
                'pr_count' => 2,
            ]
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            [
            ]
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('8'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            [
                'pr_count' => 3,
            ]
        ));

        /** @var FindPersonalRecordsPerMonthResponse $response */
        $response = $this->queryHandler->handle(new FindPersonalRecordsPerMonth(Year::fromInt(2024)));
        $this->assertEquals(
            [
                [Month::fromDate(SerializableDateTime::fromString('2024-03-01')), 3],
                [Month::fromDate(SerializableDateTime::fromString('2024-01-01')), 5],
            ],
            $response->getPersonalRecordsPerMonth(),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindPersonalRecordsPerMonthQueryHandler($this->getConnection());
    }
}
