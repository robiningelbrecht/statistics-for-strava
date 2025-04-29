<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Time\Dates;

final class Trivia
{
    public static ?Trivia $instance = null;

    private readonly int $totalKudosReceived;
    private readonly Activity $firstActivity;
    private readonly Activity $earliestActivity;
    private readonly Activity $latestActivity;
    private readonly Activity $longestActivity;
    private readonly Activity $activityWithMostElevation;
    private readonly Activity $mostKudotedActivity;
    private readonly Dates $mostConsecutiveDaysOfWorkingOut;
    private readonly Kilogram $totalCarbonSaved;

    private function __construct(
        private readonly Activities $activities,
    ) {
        $this->totalKudosReceived = (int) $this->activities->sum(fn (Activity $activity) => $activity->getKudoCount());
        $this->firstActivity = $this->determineFirstActivity();
        $this->earliestActivity = $this->determineEarliestActivity();
        $this->latestActivity = $this->determineLatestActivity();
        $this->longestActivity = $this->determineLongestWorkout();
        $this->activityWithMostElevation = $this->determineActivityWithHighestElevation();
        $this->mostKudotedActivity = $this->determineMostKudotedActivity();
        $this->mostConsecutiveDaysOfWorkingOut = Dates::fromDates($this->activities->map(
            fn (Activity $activity) => $activity->getStartDate(),
        ))->getLongestConsecutiveDateRange();
        $this->totalCarbonSaved = Kilogram::from($this->activities->sum(fn (Activity $activity) => $activity->getCarbonSaved()->toFloat()));
    }

    public static function getInstance(Activities $activities): self
    {
        if (null === self::$instance) {
            self::$instance = new self($activities);
        }

        return self::$instance;
    }

    public function getTotalKudosReceived(): int
    {
        return $this->totalKudosReceived;
    }

    public function getTotalCarbonSaved(): Kilogram
    {
        return $this->totalCarbonSaved;
    }

    public function getMostKudotedActivity(): Activity
    {
        return $this->mostKudotedActivity;
    }

    public function getFirstActivity(): Activity
    {
        return $this->firstActivity;
    }

    public function getEarliestActivity(): Activity
    {
        return $this->earliestActivity;
    }

    public function getLatestActivity(): Activity
    {
        return $this->latestActivity;
    }

    public function getLongestWorkout(): Activity
    {
        return $this->longestActivity;
    }

    public function getActivityWithMostElevation(): Activity
    {
        return $this->activityWithMostElevation;
    }

    public function getMostConsecutiveDaysOfWorkingOut(): Dates
    {
        return $this->mostConsecutiveDaysOfWorkingOut;
    }

    private function determineFirstActivity(): Activity
    {
        /** @var Activity $fistActivity */
        $fistActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate() > $fistActivity->getStartDate()) {
                continue;
            }
            $fistActivity = $activity;
        }

        return $fistActivity;
    }

    private function determineEarliestActivity(): Activity
    {
        /** @var Activity $earliestActivity */
        $earliestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() > $earliestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $earliestActivity = $activity;
        }

        return $earliestActivity;
    }

    private function determineLatestActivity(): Activity
    {
        /** @var Activity $latestActivity */
        $latestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() < $latestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $latestActivity = $activity;
        }

        return $latestActivity;
    }

    private function determineLongestWorkout(): Activity
    {
        /** @var Activity $longestActivity */
        $longestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getMovingTimeInSeconds() < $longestActivity->getMovingTimeInSeconds()) {
                continue;
            }
            $longestActivity = $activity;
        }

        return $longestActivity;
    }

    private function determineActivityWithHighestElevation(): Activity
    {
        /** @var Activity $mostElevationActivity */
        $mostElevationActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getElevation()->toFloat() < $mostElevationActivity->getElevation()->toFloat()) {
                continue;
            }
            $mostElevationActivity = $activity;
        }

        return $mostElevationActivity;
    }

    private function determineMostKudotedActivity(): Activity
    {
        /** @var Activity $mostKudotedActivity */
        $mostKudotedActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getKudoCount() < $mostKudotedActivity->getKudoCount()) {
                continue;
            }
            $mostKudotedActivity = $activity;
        }

        return $mostKudotedActivity;
    }
}
