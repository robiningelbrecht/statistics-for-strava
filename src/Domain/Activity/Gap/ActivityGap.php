<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

final readonly class ActivityGap
{
    /**
     * @param array<int, ActivityGapSplit> $metricSplits
     * @param array<int, ActivityGapSplit> $imperialSplits
     */
    public function __construct(
        private SecPerKm $overallGapPaceInSecondsPerKm,
        private array $metricSplits,
        private array $imperialSplits,
    ) {
    }

    public function getOverallGapPaceInSecondsPerKm(): SecPerKm
    {
        return $this->overallGapPaceInSecondsPerKm;
    }

    public function getSplit(UnitSystem $unitSystem, int $splitNumber): ?ActivityGapSplit
    {
        return match ($unitSystem) {
            UnitSystem::METRIC => $this->metricSplits[$splitNumber] ?? null,
            UnitSystem::IMPERIAL => $this->imperialSplits[$splitNumber] ?? null,
        };
    }
}
