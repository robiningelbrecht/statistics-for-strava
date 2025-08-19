<?php

declare(strict_types=1);

namespace App\Domain\Activity\YearlyDistance;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\YearlyDistance\FindYearlyStats\FindYearlyStatsResponse;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
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
                'movingTimeInSeconds' => Seconds::zero(),
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
                'movingTime' => CarbonInterval::seconds($yearlyStats['movingTime']->toInt())->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'movingTimeInSeconds' => $yearlyStats['movingTime'],
                'differenceInDistanceYearBefore' => null,
                'differenceInElevationYearBefore' => null,
                'differenceInMovingTimeYearBefore' => null,
            ];
        }

        foreach ($years as $year) {
            if (!isset($statistics[$year->toInt() - 1]['totalDistance'])) {
                continue;
            }

            $currentYear = $statistics[$year->toInt()];
            $previousYear = $statistics[$year->toInt() - 1];

            $differenceInMovingTime = $currentYear['movingTimeInSeconds']->toInt() - $previousYear['movingTimeInSeconds']->toInt();

            $statistics[$year->toInt()]['differenceInDistanceYearBefore'] = Kilometer::from($currentYear['totalDistance']->toFloat() - $previousYear['totalDistance']->toFloat());
            $statistics[$year->toInt()]['differenceInElevationYearBefore'] = Meter::from($currentYear['totalElevation']->toFloat() - $previousYear['totalElevation']->toFloat());
            $statistics[$year->toInt()]['differenceInMovingTimeInSecondsYearBefore'] = Seconds::from($differenceInMovingTime);
            $statistics[$year->toInt()]['differenceInMovingTimeYearBefore'] = CarbonInterval::seconds(abs($differenceInMovingTime))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        return $statistics;
    }
}
