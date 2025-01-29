<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\DaytimeStats;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Carbon\CarbonInterval;

final readonly class DaytimeStats
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public static function create(
        Activities $activities,
    ): self {
        return new self($activities);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $statistics = [];
        $totalMovingTime = $this->activities->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

        foreach (Daytime::cases() as $daytime) {
            $statistics[$daytime->value] = [
                'daytime' => $daytime,
                'numberOfWorkouts' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'percentage' => 0,
                'averageDistance' => 0,
            ];
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $daytime = Daytime::fromSerializableDateTime($activity->getStartDate());

            ++$statistics[$daytime->value]['numberOfWorkouts'];
            $statistics[$daytime->value]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$daytime->value]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$daytime->value]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$daytime->value]['averageDistance'] = $statistics[$daytime->value]['totalDistance'] / $statistics[$daytime->value]['numberOfWorkouts'];
            $statistics[$daytime->value]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$daytime->value]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$daytime->value]['percentage'] = round($statistics[$daytime->value]['movingTime'] / $totalMovingTime * 100, 2);
        }

        foreach ($statistics as $daytime => $statistic) {
            $statistics[$daytime]['totalDistance'] = Kilometer::from($statistic['totalDistance']);
            $statistics[$daytime]['averageDistance'] = Kilometer::from($statistic['averageDistance']);
            $statistics[$daytime]['totalElevation'] = Meter::from($statistic['totalElevation']);
        }

        return $statistics;
    }
}
