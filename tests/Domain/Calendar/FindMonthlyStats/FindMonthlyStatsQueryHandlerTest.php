<?php

namespace App\Tests\Domain\Calendar\FindMonthlyStats;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Calendar\FindMonthlyStats\FindMonthlyStatsQueryHandler;
use App\Domain\Calendar\Month;
use App\Domain\Gear\GearId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
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

        /** @var \App\Domain\Calendar\FindMonthlyStats\FindMonthlyStatsResponse $response */
        $response = $this->queryHandler->handle(new FindMonthlyStats());

        $this->assertMatchesJsonSnapshot($response->getForMonth(Month::fromDate(SerializableDateTime::fromString('2024-01-03 00:00:00'))));
        $this->assertNull($response->getForMonth(Month::fromDate(SerializableDateTime::fromString('2026-01-03 00:00:00'))));
        $this->assertMatchesJsonSnapshot($response->getForMonthAndActivityType(
            Month::fromDate(SerializableDateTime::fromString('2024-01-03 00:00:00')),
            ActivityType::RIDE
        ));
        $this->assertMatchesJsonSnapshot($response->getForMonthAndSportType(
            Month::fromDate(SerializableDateTime::fromString('2024-01-03 00:00:00')),
            SportType::RIDE
        ));

        $this->assertEquals(
            Month::fromDate(SerializableDateTime::fromString('2023-01-01 00:00:00')),
            $response->getFirstMonthFor(ActivityType::RIDE)
        );
        $this->assertEquals(
            Month::fromDate(SerializableDateTime::fromString('2025-01-01 00:00:00')),
            $response->getLastMonthFor(ActivityType::RIDE)
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindMonthlyStatsQueryHandler($this->getConnection());
    }
}
