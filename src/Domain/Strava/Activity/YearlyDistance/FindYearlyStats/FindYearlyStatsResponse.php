<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance\FindYearlyStats;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\Year;

readonly class FindYearlyStatsResponse implements Response
{
    public function __construct(
        /** @var array<int, array{'year': Year, 'activityType': ActivityType, 'numberOfActivities': int, 'distance': Kilometer, 'elevation': Meter, 'movingTime': Seconds, 'calories': int}> */
        private array $yearlyStats,
    ) {
    }

    /**
     * @return array{'year': Year, 'activityType': ActivityType, 'numberOfActivities': int, 'distance': Kilometer, 'elevation': Meter, 'movingTime': Seconds, 'calories': int}|null
     */
    public function getFor(Year $year, ActivityType $activityType): ?array
    {
        foreach ($this->yearlyStats as $yearlyStat) {
            if ($year->toInt() !== $yearlyStat['year']->toInt()) {
                continue;
            }
            if ($activityType !== $yearlyStat['activityType']) {
                continue;
            }

            return $yearlyStat;
        }

        return null;
    }
}
