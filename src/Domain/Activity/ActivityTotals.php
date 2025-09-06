<?php

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

use function Symfony\Component\Translation\t;

final class ActivityTotals
{
    public static ?ActivityTotals $instance = null;

    private readonly Kilometer $totalDistance;
    private readonly Meter $totalElevation;
    private readonly int $totalCalories;
    private readonly int $totalMovingTimeInSeconds;
    private readonly int $totalActivities;

    private function __construct(
        private readonly Activities $activities,
        private readonly SerializableDateTime $now,
    ) {
        $this->totalDistance = Kilometer::from(
            $this->activities->sum(fn (Activity $activity) => $activity->getDistance()->toFloat())
        );
        $this->totalElevation = Meter::from(
            $this->activities->sum(fn (Activity $activity) => $activity->getElevation()->toFloat())
        );
        $this->totalCalories = (int) $this->activities->sum(fn (Activity $activity) => $activity->getCalories());
        $this->totalMovingTimeInSeconds = (int) $this->activities->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());
        $this->totalActivities = count($this->activities);
    }

    public static function getInstance(
        Activities $activities,
        SerializableDateTime $now): self
    {
        if (null === self::$instance) {
            self::$instance = new self(
                activities: $activities,
                now: $now,
            );
        }

        return self::$instance;
    }

    public function getDistance(): Kilometer
    {
        return $this->totalDistance;
    }

    public function getElevation(): Meter
    {
        return $this->totalElevation;
    }

    public function getCalories(): int
    {
        return $this->totalCalories;
    }

    public function getTotalActivities(): int
    {
        return $this->totalActivities;
    }

    public function getMovingTimeFormatted(): string
    {
        return CarbonInterval::seconds($this->totalMovingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
    }

    public function getMovingTimeInHours(): int
    {
        return (int) round(CarbonInterval::seconds($this->totalMovingTimeInSeconds)->cascade()->totalHours);
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->activities->getFirstActivityStartDate();
    }

    public function getDailyAverage(): Kilometer
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return Kilometer::zero();
        }

        return Kilometer::from($this->getDistance()->toFloat() / $diff->days);
    }

    public function getWeeklyAverage(): Kilometer
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return Kilometer::zero();
        }

        return Kilometer::from($this->getDistance()->toFloat() / ceil($diff->days / 7));
    }

    public function getMonthlyAverage(): Kilometer
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return Kilometer::zero();
        }

        return Kilometer::from($this->getDistance()->toFloat() / (($diff->y * 12) + $diff->m + 1));
    }

    public function getTotalDaysSinceFirstActivity(): string
    {
        $join = t('and');

        return CarbonInterval::diff($this->getStartDate(), $this->now)->cascade()->forHumans(['minimumUnit' => 'day', 'join' => [
            ' ', sprintf(' %s ', $join),
        ], 'parts' => 2]);
    }

    public function getTotalDaysOfWorkingOut(): int
    {
        return count(array_unique($this->activities->map(fn (Activity $activity) => $activity->getStartDate()->format('Ymd'))));
    }
}
