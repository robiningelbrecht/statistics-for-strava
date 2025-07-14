<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\YearlyDistance\FindYearlyStats\FindYearlyStatsResponse;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\Years;
use Carbon\CarbonInterval;

final readonly class YearlyStatistics
{
    private function __construct(
        private FindYearlyStatsResponse $yearlyStats,
        private ActivityType $activityType,
        private Years $years,
    ) {
    }

    public static function create(
        FindYearlyStatsResponse $yearlyStats,
        ActivityType $activityType,
        Years $years,
    ): self {
        return new self(
            yearlyStats: $yearlyStats,
            activityType: $activityType,
            years: $years
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function getStatistics(): array
    {
        $statistics = [];
        $years = $this->years->reverse();
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($years as $year) {
            $statistics[$year->toInt()] = [
                'year' => (string) $year,
                'numberOfRides' => 0,
                'totalDistance' => Kilometer::zero(),
                'totalElevation' => Meter::zero(),
                'totalCalories' => 0,
                'differenceInDistanceYearBefore' => null,
                'movingTime' => CarbonInterval::seconds(0)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            ];

            if (!$yearlyStats = $this->yearlyStats->getFor(
                year: $year,
                activityType: $this->activityType
            )) {
                continue;
            }

            $statistics[$year->toInt()] = [
                'year' => (string) $year,
                'numberOfRides' => $yearlyStats['numberOfActivities'],
                'totalDistance' => $yearlyStats['distance'],
                'totalElevation' => $yearlyStats['elevation'],
                'totalCalories' => $yearlyStats['calories'],
                'differenceInDistanceYearBefore' => null,
                'movingTime' => CarbonInterval::seconds($yearlyStats['movingTime']->toInt())->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            ];
        }

        foreach ($years as $year) {
            if (!isset($statistics[$year->toInt() - 1]['totalDistance'])) {
                continue;
            }

            $currentYearDistance = $statistics[$year->toInt()]['totalDistance']->toFloat();
            $previousYearDistance = $statistics[$year->toInt() - 1]['totalDistance']->toFloat();
            $statistics[$year->toInt()]['differenceInDistanceYearBefore'] = Kilometer::from($currentYearDistance - $previousYearDistance);
        }

        return $statistics;
    }
}
