<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

final readonly class GapSegment
{
    private function __construct(
        private float $distanceInMeters,
        private int $durationInSeconds,
        private float $grade,
        private float $actualPaceInSecondsPerKm,
        private float $gapMultiplier,
        private float $gapPaceInSecondsPerKm,
    ) {
    }

    public static function create(
        float $distanceInMeters,
        int $durationInSeconds,
        float $grade,
        float $actualPaceInSecondsPerKm,
        float $gapMultiplier,
        float $gapPaceInSecondsPerKm,
    ): self {
        return new self(
            distanceInMeters: $distanceInMeters,
            durationInSeconds: $durationInSeconds,
            grade: $grade,
            actualPaceInSecondsPerKm: $actualPaceInSecondsPerKm,
            gapMultiplier: $gapMultiplier,
            gapPaceInSecondsPerKm: $gapPaceInSecondsPerKm,
        );
    }

    public function getDistanceInMeters(): float
    {
        return $this->distanceInMeters;
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }

    public function getGrade(): float
    {
        return $this->grade;
    }

    public function getActualPaceInSecondsPerKm(): float
    {
        return $this->actualPaceInSecondsPerKm;
    }

    public function getGapMultiplier(): float
    {
        return $this->gapMultiplier;
    }

    public function getGapPaceInSecondsPerKm(): float
    {
        return $this->gapPaceInSecondsPerKm;
    }
}
