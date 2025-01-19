<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Carbon\CarbonInterval;

final readonly class DistanceBreakdown
{
    private function __construct(
        private Activities $activities,
        private UnitSystem $unitSystem,
    ) {
    }

    public static function create(
        Activities $activities,
        UnitSystem $unitSystem,
    ): self {
        return new self(
            activities: $activities,
            unitSystem: $unitSystem
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        if ($this->activities->isEmpty()) {
            return [];
        }

        $numberOfBreakdowns = 11;
        $statistics = [];
        $longestDistanceForActivity = Kilometer::from($this->activities->max(
            fn (Activity $activity) => $activity->getDistance()->toFloat()
        ))->toUnitSystem($this->unitSystem);

        if ($longestDistanceForActivity->isZeroOrLower()) {
            return [];
        }

        $breakdownOnDistance = ceil(($longestDistanceForActivity->toFloat() / $numberOfBreakdowns) / 5) * 5;

        $range = range($breakdownOnDistance, ceil($longestDistanceForActivity->toFloat() / $breakdownOnDistance) * $breakdownOnDistance, $breakdownOnDistance);
        foreach ($range as $breakdownLimit) {
            $statistics[$breakdownLimit] = [
                'label' => sprintf('%d - %d %s', $breakdownLimit - $breakdownOnDistance, $breakdownLimit, $longestDistanceForActivity->getSymbol()),
                'numberOfWorkouts' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'averageDistance' => 0,
                'averageSpeed' => 0,
            ];
        }

        foreach ($this->activities as $activity) {
            /** @var Activity $activity */
            $distance = $activity->getDistance()->toUnitSystem($this->unitSystem);
            if ($distance->isZeroOrLower()) {
                continue;
            }
            $elevation = $activity->getElevation()->toUnitSystem($this->unitSystem);
            $distanceBreakdown = ceil($distance->toFloat() / $breakdownOnDistance) * $breakdownOnDistance;

            ++$statistics[$distanceBreakdown]['numberOfWorkouts'];
            $statistics[$distanceBreakdown]['totalDistance'] += $distance->toFloat();
            $statistics[$distanceBreakdown]['totalElevation'] += $elevation->toFloat();
            $statistics[$distanceBreakdown]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$distanceBreakdown]['averageDistance'] = $statistics[$distanceBreakdown]['totalDistance'] / $statistics[$distanceBreakdown]['numberOfWorkouts'];
            if ($statistics[$distanceBreakdown]['movingTime'] > 0) {
                $statistics[$distanceBreakdown]['averageSpeed'] = ($statistics[$distanceBreakdown]['totalDistance'] / $statistics[$distanceBreakdown]['movingTime']) * 3600;
            }
            $statistics[$distanceBreakdown]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$distanceBreakdown]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        foreach ($statistics as $distanceBreakdown => $statistic) {
            $statistics[$distanceBreakdown]['totalDistance'] = $this->unitSystem->distance($statistic['totalDistance']);
            $statistics[$distanceBreakdown]['averageDistance'] = $this->unitSystem->distance($statistic['averageDistance']);
            $statistics[$distanceBreakdown]['totalElevation'] = $this->unitSystem->elevation($statistic['totalElevation']);
            $statistics[$distanceBreakdown]['averageSpeed'] = $this->unitSystem->speed($statistic['averageSpeed']);
        }

        return $statistics;
    }
}
