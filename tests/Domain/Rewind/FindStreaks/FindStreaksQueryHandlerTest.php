<?php

namespace App\Tests\Domain\Rewind\FindStreaks;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Rewind\FindStreaks\FindStreaks;
use App\Domain\Rewind\FindStreaks\FindStreaksQueryHandler;
use App\Domain\Rewind\FindStreaks\FindStreaksResponse;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use PHPUnit\Framework\Attributes\DataProvider;

class FindStreaksQueryHandlerTest extends ContainerTestCase
{
    #[DataProvider(methodName: 'provideData')]
    public function testHandle(Clock $clock, FindStreaksResponse $expected): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStartDateTime(SerializableDateTime::fromString('2025-12-27'))
                ->build(), []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStartDateTime(SerializableDateTime::fromString('2025-12-26'))
                ->build(), []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withStartDateTime(SerializableDateTime::fromString('2025-12-21'))
                ->build(), []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withStartDateTime(SerializableDateTime::fromString('2025-11-27'))
                ->build(), []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(5))
                ->withStartDateTime(SerializableDateTime::fromString('2025-11-26'))
                ->build(), []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(6))
                ->withStartDateTime(SerializableDateTime::fromString('2025-11-25'))
                ->build(), []
        ));

        $findStreaksQueryHandler = new FindStreaksQueryHandler(
            $this->getConnection(),
            $clock
        );

        $this->assertEquals(
            $expected,
            $findStreaksQueryHandler->handle(
                new FindStreaks(Years::all($clock->getCurrentDateTimeImmutable()))
            )
        );
    }

    public function testHandleFindStreaksWhenDataIsMissing(): void
    {
        $findStreaksQueryHandler = new FindStreaksQueryHandler(
            $this->getConnection(),
            $clock = PausedClock::fromString('2025-12-27')
        );

        $this->assertEquals(
            new FindStreaksResponse(
                longestDayStreak: 0,
                currentDayStreak: 0,
                longestWeekStreak: 0,
                currentWeekStreak: 0,
                longestMonthStreak: 0,
                currentMonthStreak: 0,
            ),
            $findStreaksQueryHandler->handle(
                new FindStreaks(Years::all($clock->getCurrentDateTimeImmutable()))
            )
        );
    }

    public static function provideData(): iterable
    {
        return [
            [
                PausedClock::fromString('2025-12-27'),
                new FindStreaksResponse(
                    longestDayStreak: 3,
                    currentDayStreak: 2,
                    longestWeekStreak: 2,
                    currentWeekStreak: 2,
                    longestMonthStreak: 2,
                    currentMonthStreak: 2,
                ),
            ],
            [
                PausedClock::fromString('2026-01-27'),
                new FindStreaksResponse(
                    longestDayStreak: 3,
                    currentDayStreak: 0,
                    longestWeekStreak: 2,
                    currentWeekStreak: 0,
                    longestMonthStreak: 2,
                    currentMonthStreak: 0,
                ),
            ],
        ];
    }
}
