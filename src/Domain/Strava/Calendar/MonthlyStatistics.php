<?php

namespace App\Domain\Strava\Calendar;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\Challenges;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Carbon\CarbonInterval;

final readonly class MonthlyStatistics
{
    /** @var array<string, mixed> */
    private array $statistics;

    private function __construct(
        private Activities $activities,
        private Challenges $challenges,
        private Months $months,
    ) {
        $this->statistics = $this->buildStatistics();
    }

    public static function create(
        Activities $activities,
        Challenges $challenges,
        Months $months): self
    {
        return new self(
            activities: $activities,
            challenges: $challenges,
            months: $months
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatistics(): array
    {
        $statistics = [];
        /** @var Month $month */
        foreach ($this->months as $month) {
            $statistics[$month->getId()] = [
                'id' => $month->getId(),
                'month' => $month->getLabel(),
                'numberOfWorkouts' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'totalCalories' => 0,
                'movingTimeInSeconds' => 0,
                'challengesCompleted' => count($this->challenges->filter(
                    fn (Challenge $challenge) => $challenge->getCreatedOn()->format(Month::MONTH_ID_FORMAT) == $month->getId()
                )),
            ];
        }

        $statistics = array_reverse($statistics, true);

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $month = $activity->getStartDate()->format(Month::MONTH_ID_FORMAT);

            ++$statistics[$month]['numberOfWorkouts'];
            $statistics[$month]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$month]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$month]['movingTimeInSeconds'] += $activity->getMovingTimeInSeconds();
            $statistics[$month]['totalCalories'] += $activity->getCalories();
        }

        $statistics = array_filter($statistics, fn (array $statistic) => $statistic['numberOfWorkouts'] > 0);

        foreach ($statistics as &$statistic) {
            $statistic['movingTime'] = CarbonInterval::seconds($statistic['movingTimeInSeconds'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistic['totalDistance'] = Kilometer::from($statistic['totalDistance']);
            $statistic['totalElevation'] = Meter::from($statistic['totalElevation']);
        }

        return $statistics;
    }

    /**
     * @return array<mixed>
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * @return array<string,mixed>
     */
    public function getStatisticsForMonth(Month $month): array
    {
        return $this->statistics[$month->getId()] ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function getTotals(): array
    {
        return $this->getTotalsForActivities($this->activities);
    }

    /**
     * @return array<string,mixed>
     */
    public function getTotalsForSportType(SportType $sportType): array
    {
        return $this->getTotalsForActivities($this->activities->filterOnSportType($sportType));
    }

    /**
     * @return array<string,mixed>
     */
    private function getTotalsForActivities(Activities $activities): array
    {
        return [
            'numberOfWorkouts' => count($activities),
            'totalDistance' => Kilometer::from($activities->sum(fn (Activity $activity) => $activity->getDistance()->toFloat())),
            'totalElevation' => Meter::from($activities->sum(fn (Activity $activity) => $activity->getElevation()->toFloat())),
            'totalCalories' => $activities->sum(fn (Activity $activity) => $activity->getCalories()),
            'movingTime' => CarbonInterval::seconds($activities->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds()))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
        ];
    }
}
