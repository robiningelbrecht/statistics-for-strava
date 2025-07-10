<?php

namespace App\Tests\Domain\Strava\Calendar\FindMonthlyStats;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStatsQueryHandler;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class FindMonthlyStatsQueryHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private FindMonthlyStatsQueryHandler $queryHandler;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('0'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
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
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('8'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));

        /** @var \App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStatsResponse $response */
        $response = $this->queryHandler->handle(new FindMonthlyStats());

        $this->assertMatchesJsonSnapshot($response->getForMonth(Month::fromDate(SerializableDateTime::fromString('2024-01-03 00:00:00'))));
        $this->assertNull($response->getForMonth(Month::fromDate(SerializableDateTime::fromString('2026-01-03 00:00:00'))));
        $this->assertMatchesJsonSnapshot($response->getForMonthAndActivityType(
            Month::fromDate(SerializableDateTime::fromString('2024-01-03 00:00:00')),
            ActivityType::RIDE
        ));
        $this->assertMatchesJsonSnapshot($response->getForSportType(SportType::VIRTUAL_RIDE));

        $this->assertEquals(
            Month::fromDate(SerializableDateTime::fromString('2023-01-01')),
            $response->getFirstMonthFor(ActivityType::RIDE)
        );
        $this->assertEquals(
            Month::fromDate(SerializableDateTime::fromString('2025-01-01')),
            $response->getLastMonthFor(ActivityType::RIDE)
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindMonthlyStatsQueryHandler($this->getConnection());
    }
}
