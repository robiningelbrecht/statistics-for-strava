<?php

declare(strict_types=1);

namespace App\Application\Build\BuildActivitiesHtml;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

final readonly class BuildActivityGapData
{
    /**
     * @param array<int, BuildActivityGapSplitData> $metricSplits
     * @param array<int, BuildActivityGapSplitData> $imperialSplits
     */
    public function __construct(
        private SecPerKm $overallGapPaceInSecondsPerKm,
        private array $metricSplits,
        private array $imperialSplits,
        private array $profileChartData,
    ) {
    }

    public function getOverallGapPaceInSecondsPerKm(): SecPerKm
    {
        return $this->overallGapPaceInSecondsPerKm;
    }

    public function getSplit(UnitSystem $unitSystem, int $splitNumber): ?BuildActivityGapSplitData
    {
        return match ($unitSystem) {
            UnitSystem::METRIC => $this->metricSplits[$splitNumber] ?? null,
            UnitSystem::IMPERIAL => $this->imperialSplits[$splitNumber] ?? null,
        };
    }

    /**
     * @return list<int>
     */
    public function getProfileChartData(): array
    {
        return $this->profileChartData;
    }
}
