<?php

declare(strict_types=1);

namespace App\Domain\Activity\WeekdayStats;

use App\Domain\Activity\Activities;
use App\Domain\Activity\Activity;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Carbon\CarbonInterval;

use function Symfony\Component\Translation\t;

final readonly class WeekdayStats
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public static function create(
        Activities $activities,
    ): self {
        return new self(
            activities: $activities,
        );
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $statistics = [];
        $daysOfTheWeekMap = [
            (string) t('Sunday'),
            (string) t('Monday'),
            (string) t('Tuesday'),
            (string) t('Wednesday'),
            (string) t('Thursday'),
            (string) t('Friday'),
            (string) t('Saturday'),
        ];
        $totalMovingTime = $this->activities->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

        foreach ([1, 2, 3, 4, 5, 6, 0] as $weekDay) {
            $statistics[(string) $daysOfTheWeekMap[$weekDay]] = [
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
            $weekDay = (string) $daysOfTheWeekMap[$activity->getStartDate()->format('w')];

            ++$statistics[$weekDay]['numberOfWorkouts'];

            $statistics[$weekDay]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$weekDay]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$weekDay]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$weekDay]['averageDistance'] = $statistics[$weekDay]['totalDistance'] / $statistics[$weekDay]['numberOfWorkouts'];
            $statistics[$weekDay]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$weekDay]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$weekDay]['percentage'] = round($statistics[$weekDay]['movingTime'] / $totalMovingTime * 100, 2);
        }

        foreach ($statistics as $weekDay => $statistic) {
            $statistics[$weekDay]['totalDistance'] = Kilometer::from($statistic['totalDistance']);
            $statistics[$weekDay]['averageDistance'] = Kilometer::from($statistic['averageDistance']);
            $statistics[$weekDay]['totalElevation'] = Meter::from($statistic['totalElevation']);
        }

        return $statistics;
    }
}
