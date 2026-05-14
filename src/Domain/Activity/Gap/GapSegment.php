<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

final readonly class GapSegment
{
    private function __construct(
        private float $distanceInMeters,
        private int $durationInSeconds,
        private float $grade,
        private float $gapMultiplier,
    ) {
    }

    public static function create(
        float $distanceInMeters,
        int $durationInSeconds,
        float $grade,
        float $gapMultiplier,
    ): self {
        return new self(
            distanceInMeters: $distanceInMeters,
            durationInSeconds: $durationInSeconds,
            grade: $grade,
            gapMultiplier: $gapMultiplier,
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

    public function getGapMultiplier(): float
    {
        return $this->gapMultiplier;
    }
}
