<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Training;

final readonly class TimeInZones
{
    private function __construct(
        private int $z1Time,
        private int $z2Time,
        private int $z3Time,
        private int $z4Time,
        private int $z5Time,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            z1Time: $data['z1Time'] ?? 0,
            z2Time: $data['z2Time'] ?? 0,
            z3Time: $data['z3Time'] ?? 0,
            z4Time: $data['z4Time'] ?? 0,
            z5Time: $data['z5Time'] ?? 0,
        );
    }

    public function getZ1Time(): int
    {
        return $this->z1Time;
    }

    public function getZ2Time(): int
    {
        return $this->z2Time;
    }

    public function getZ3Time(): int
    {
        return $this->z3Time;
    }

    public function getZ4Time(): int
    {
        return $this->z4Time;
    }

    public function getZ5Time(): int
    {
        return $this->z5Time;
    }

    public function getTotalTime(): int
    {
        return $this->z1Time + $this->z2Time + $this->z3Time + $this->z4Time + $this->z5Time;
    }

    public function getPercentageInLowZones(): float
    {
        if (0 === $this->getTotalTime()) {
            return 0;
        }

        return ($this->z1Time + $this->z2Time) / $this->getTotalTime() * 100;
    }

    public function getPercentageInMediumZone(): float
    {
        if (0 === $this->getTotalTime()) {
            return 0;
        }

        return $this->z3Time / $this->getTotalTime() * 100;
    }

    public function getPercentageInHighZones(): float
    {
        if (0 === $this->getTotalTime()) {
            return 0;
        }

        return ($this->z4Time + $this->z5Time) / $this->getTotalTime() * 100;
    }
}
