<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

use App\Domain\Strava\Activity\Training\TrainingType;

final readonly class TimeInHeartRateZones
{
    private function __construct(
        private int $timeInZoneOne,
        private int $timeInZoneTwo,
        private int $timeInZoneThree,
        private int $timeInZoneFour,
        private int $timeInZoneFive,
    ) {
    }

    public static function create(
        int $timeInZoneOne,
        int $timeInZoneTwo,
        int $timeInZoneThree,
        int $timeInZoneFour,
        int $timeInZoneFive,
    ): self {
        return new self(
            timeInZoneOne: $timeInZoneOne,
            timeInZoneTwo: $timeInZoneTwo,
            timeInZoneThree: $timeInZoneThree,
            timeInZoneFour: $timeInZoneFour,
            timeInZoneFive: $timeInZoneFive,
        );
    }

    public function getTimeInZoneOne(): int
    {
        return $this->timeInZoneOne;
    }

    public function getTimeInZoneTwo(): int
    {
        return $this->timeInZoneTwo;
    }

    public function getTimeInZoneThree(): int
    {
        return $this->timeInZoneThree;
    }

    public function getTimeInZoneFour(): int
    {
        return $this->timeInZoneFour;
    }

    public function getTimeInZoneFive(): int
    {
        return $this->timeInZoneFive;
    }

    public function getTotalTime(): int
    {
        return $this->getTimeInZoneOne() + $this->getTimeInZoneTwo()
            + $this->getTimeInZoneThree() + $this->getTimeInZoneFour() + $this->getTimeInZoneFive();
    }

    public function getPercentageInLowZones(): float
    {
        if (0 === $this->getTotalTime()) {
            return 0;
        }

        return ($this->getTimeInZoneOne() + $this->getTimeInZoneTwo()) / $this->getTotalTime() * 100;
    }

    public function getPercentageInMediumZone(): float
    {
        if (0 === $this->getTotalTime()) {
            return 0;
        }

        return $this->getTimeInZoneThree() / $this->getTotalTime() * 100;
    }

    public function getPercentageInHighZones(): float
    {
        if (0 === $this->getTotalTime()) {
            return 0;
        }

        return ($this->getTimeInZoneFour() + $this->getTimeInZoneFive()) / $this->getTotalTime() * 100;
    }

    public function getTrainingType(): TrainingType
    {
        $low = $this->getPercentageInLowZones();
        $medium = $this->getPercentageInMediumZone();
        $high = $this->getPercentageInHighZones();

        if ($low >= 75 && $low <= 85 && $medium >= 5 && $medium <= 10 && $high >= 10 && $high <= 20) {
            return TrainingType::POLARIZED;
        }

        if ($low >= 60 && $low <= 75 && $medium >= 15 && $medium <= 25 && $high >= 10 && $high <= 20) {
            return TrainingType::PYRAMIDAL;
        }

        if ($low >= 50 && $low <= 65 && $medium >= 25 && $medium <= 35 && $high >= 10 && $high <= 20) {
            return TrainingType::THRESHOLD;
        }

        if ($low >= 45 && $low <= 60 && $medium >= 15 && $medium <= 25 && $high >= 25 && $high <= 40) {
            return TrainingType::HIIT;
        }

        if ($low >= 85 && $low <= 95 && $medium >= 3 && $medium <= 8 && $high >= 2 && $high <= 7) {
            return TrainingType::BASE;
        }

        return TrainingType::UNIQUE_OTHER;
    }
}
