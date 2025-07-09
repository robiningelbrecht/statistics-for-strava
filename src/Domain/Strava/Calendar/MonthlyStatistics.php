<?php

namespace App\Domain\Strava\Calendar;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStatsResponse;
use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\Challenges;
use Carbon\CarbonInterval;

final readonly class MonthlyStatistics
{
    private function __construct(
        private FindMonthlyStatsResponse $monthlyStats,
        private Challenges $challenges,
        private Months $months,
    ) {
    }

    public static function create(
        FindMonthlyStatsResponse $monthlyStats,
        Challenges $challenges,
        Months $months): self
    {
        return new self(
            monthlyStats: $monthlyStats,
            challenges: $challenges,
            months: $months
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $statistics = [];
        /** @var Month $month */
        foreach ($this->months as $month) {
            [$numberOfActivities, $distance, $elevation, $movingTime, $calories] = $this->monthlyStats->getForMonth($month);
            $statistics[$month->getId()] = [
                'id' => $month->getId(),
                'month' => $month->getLabel(),
                'numberOfWorkouts' => $numberOfActivities,
                'totalDistance' => $distance,
                'totalElevation' => $elevation,
                'totalCalories' => $calories,
                'movingTime' => CarbonInterval::seconds($movingTime->toInt())->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'challengesCompleted' => count($this->challenges->filter(
                    fn (Challenge $challenge) => $challenge->getCreatedOn()->format(Month::MONTH_ID_FORMAT) == $month->getId()
                )),
            ];
        }

        $statistics = array_reverse($statistics, true);

        return array_filter($statistics, fn (array $statistic) => $statistic['numberOfWorkouts'] > 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function getStatisticsForMonth(Month $month): array
    {
        [$numberOfActivities, $distance, $elevation, $movingTime, $calories] = $this->monthlyStats->getForMonth($month);
        if (0 === $numberOfActivities) {
            return [];
        }

        return [
            'id' => $month->getId(),
            'month' => $month->getLabel(),
            'numberOfWorkouts' => $numberOfActivities,
            'totalDistance' => $distance,
            'totalElevation' => $elevation,
            'totalCalories' => $calories,
            'movingTimeInSeconds' => $movingTime->toInt(),
            'challengesCompleted' => count($this->challenges->filter(
                fn (Challenge $challenge) => $challenge->getCreatedOn()->format(Month::MONTH_ID_FORMAT) == $month->getId()
            )),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getTotals(): array
    {
        [$numberOfActivities, $distance, $elevation, $movingTime, $calories] = $this->monthlyStats->getTotals();

        return [
            'numberOfWorkouts' => $numberOfActivities,
            'totalDistance' => $distance,
            'totalElevation' => $elevation,
            'movingTime' => CarbonInterval::seconds($movingTime->toInt())->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            'totalCalories' => $calories,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getTotalsForSportType(SportType $sportType): array
    {
        [$numberOfActivities, $distance, $elevation, $movingTime, $calories] = $this->monthlyStats->getForSportType($sportType);

        return [
            'numberOfWorkouts' => $numberOfActivities,
            'totalDistance' => $distance,
            'totalElevation' => $elevation,
            'totalCalories' => $calories,
            'movingTime' => CarbonInterval::seconds($movingTime->toInt())->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
        ];
    }
}
