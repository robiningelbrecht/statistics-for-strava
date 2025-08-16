<?php

declare(strict_types=1);

namespace App\Domain\Athlete\HeartRateZone;

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

    private function getTotalTime(): int
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
}
