<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ActivityTotalsTest extends ContainerTestCase
{
    #[DataProvider(methodName: 'provideActivityTotals')]
    public function testGetTotalDaysSinceFirstActivity(string $expectedResult, Activities $activities, SerializableDateTime $now): void
    {
        $this->assertEquals(
            $expectedResult,
            ActivityTotals::getInstance(
                activities: $activities,
                now: $now,
            )->getTotalDaysSinceFirstActivity()
        );
    }

    public static function provideActivityTotals(): array
    {
        return [
            [
                '1 day',
                Activities::fromArray([
                    ActivityBuilder::fromDefaults()
                        ->withStartDateTime(SerializableDateTime::fromString('2023-11-24'))
                        ->build(),
                ]),
                SerializableDateTime::fromString('2023-11-25'),
            ],
            [
                '3 weeks and 3 days',
                Activities::fromArray([
                    ActivityBuilder::fromDefaults()
                        ->withStartDateTime(SerializableDateTime::fromString('2023-11-01'))
                        ->build(),
                ]),
                SerializableDateTime::fromString('2023-11-25'),
            ],
            [
                '7 months and 2 weeks',
                Activities::fromArray([
                    ActivityBuilder::fromDefaults()
                        ->withStartDateTime(SerializableDateTime::fromString('2023-04-24'))
                        ->build(),
                ]),
                SerializableDateTime::fromString('2023-11-25'),
            ],
            [
                '1 year and 1 month',
                Activities::fromArray([
                    ActivityBuilder::fromDefaults()
                        ->withStartDateTime(SerializableDateTime::fromString('2022-11-24'))
                        ->build(),
                ]),
                SerializableDateTime::fromString('2023-11-25'),
            ],
            [
                '6 years and 6 months',
                Activities::fromArray([
                    ActivityBuilder::fromDefaults()
                        ->withStartDateTime(SerializableDateTime::fromString('2017-11-24'))
                        ->build(),
                ]),
                SerializableDateTime::fromString('2023-11-25'),
            ],
        ];
    }
}
