<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

final readonly class Gap
{
    private function __construct(
        private int $segmentCount,
        private float $distanceInMeters,
        private int $durationInSeconds,
        private ?float $actualPaceInSecondsPerKm,
        private ?float $gapPaceInSecondsPerKm,
        private float $averageGrade,
        private float $totalAdjustedDistanceInMeters,
    ) {
    }

    public static function empty(): self
    {
        return new self(
            segmentCount: 0,
            distanceInMeters: 0.0,
            durationInSeconds: 0,
            actualPaceInSecondsPerKm: null,
            gapPaceInSecondsPerKm: null,
            averageGrade: 0.0,
            totalAdjustedDistanceInMeters: 0.0,
        );
    }

    public static function create(
        int $segmentCount,
        float $distanceInMeters,
        int $durationInSeconds,
        ?float $actualPaceInSecondsPerKm,
        ?float $gapPaceInSecondsPerKm,
        float $averageGrade,
        float $totalAdjustedDistanceInMeters,
    ): self {
        return new self(
            segmentCount: $segmentCount,
            distanceInMeters: $distanceInMeters,
            durationInSeconds: $durationInSeconds,
            actualPaceInSecondsPerKm: $actualPaceInSecondsPerKm,
            gapPaceInSecondsPerKm: $gapPaceInSecondsPerKm,
            averageGrade: $averageGrade,
            totalAdjustedDistanceInMeters: $totalAdjustedDistanceInMeters,
        );
    }

    public function getSegmentCount(): int
    {
        return $this->segmentCount;
    }

    public function getDistanceInMeters(): float
    {
        return $this->distanceInMeters;
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }

    public function getActualPaceInSecondsPerKm(): ?float
    {
        return $this->actualPaceInSecondsPerKm;
    }

    public function getGapPaceInSecondsPerKm(): ?float
    {
        return $this->gapPaceInSecondsPerKm;
    }

    public function getAverageGrade(): float
    {
        return $this->averageGrade;
    }

    public function getTotalAdjustedDistanceInMeters(): float
    {
        return $this->totalAdjustedDistanceInMeters;
    }
}
