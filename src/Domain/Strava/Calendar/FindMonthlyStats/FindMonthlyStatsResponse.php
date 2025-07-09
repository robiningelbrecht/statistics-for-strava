<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar\FindMonthlyStats;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;

final readonly class FindMonthlyStatsResponse implements Response
{
    public function __construct(
        /** @var array<int, array{0: Month, 1: SportType, 2: int, 3: Kilometer, 4: Meter, 5: Seconds, 6: int}> */
        private array $statsPerMonth,
    ) {
    }

    /**
     * @return array{0: int, 1: Kilometer, 2: Meter, 3: Seconds, 4: int}
     */
    public function getForMonth(Month $month): array
    {
        $totals = [
            'numberOfActivities' => 0,
            'distance' => 0,
            'elevation' => 0,
            'movingTime' => 0,
            'calories' => 0,
        ];
        foreach ($this->statsPerMonth as $statsPerMonth) {
            [$currentMonth, $sportType, $numberOfActivities, $distance, $elevation, $movingTime, $calories] = $statsPerMonth;
            if ($currentMonth->getId() !== $month->getId()) {
                continue;
            }

            $totals['numberOfActivities'] += $numberOfActivities;
            $totals['distance'] += $distance->toFloat();
            $totals['elevation'] += $elevation->toInt();
            $totals['movingTime'] += $movingTime->toInt();
            $totals['calories'] += $calories;
        }

        return [
            $totals['numberOfActivities'],
            Kilometer::from($totals['distance']),
            Meter::from($totals['elevation']),
            Seconds::from($totals['movingTime']),
            $totals['calories'],
        ];
    }

    /**
     * @return array{0: int, 1: Kilometer, 2: Meter, 3: Seconds, 4: int}
     */
    public function getForMonthAndActivityType(Month $month, ActivityType $activityType): array
    {
        $sportTypes = $activityType->getSportTypes();
        $totals = [
            'numberOfActivities' => 0,
            'distance' => 0,
            'elevation' => 0,
            'movingTime' => 0,
            'calories' => 0,
        ];

        foreach ($this->statsPerMonth as $statsPerMonth) {
            [$currentMonth, $sportType, $numberOfActivities, $distance, $elevation, $movingTime, $calories] = $statsPerMonth;
            if ($currentMonth->getId() !== $month->getId()) {
                continue;
            }
            if (!$sportTypes->has($sportType)) {
                continue;
            }

            $totals['numberOfActivities'] += $numberOfActivities;
            $totals['distance'] += $distance->toFloat();
            $totals['elevation'] += $elevation->toInt();
            $totals['movingTime'] += $movingTime->toInt();
            $totals['calories'] += $calories;
        }

        return [
            $totals['numberOfActivities'],
            Kilometer::from($totals['distance']),
            Meter::from($totals['elevation']),
            Seconds::from($totals['movingTime']),
            $totals['calories'],
        ];
    }
}
