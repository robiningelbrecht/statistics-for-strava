<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

final readonly class AerobicDecouplingCalculator
{
    /**
     * @param array<int, mixed> $timeData
     * @param array<int, mixed> $movingData
     * @param array<int, mixed> $heartRateData
     * @param array<int, mixed> $velocityData
     */
    public function calculate(
        array $timeData,
        array $movingData,
        array $heartRateData,
        array $velocityData,
    ): ?float {
        $segments = [];
        $totalMovingDuration = 0.0;
        $count = min(count($timeData), count($movingData), count($heartRateData), count($velocityData));

        for ($i = 1; $i < $count; ++$i) {
            if (!(bool) $movingData[$i]) {
                continue;
            }
            if (!is_numeric($timeData[$i])) {
                continue;
            }
            if (!is_numeric($timeData[$i - 1])) {
                continue;
            }

            $duration = (float) $timeData[$i] - (float) $timeData[$i - 1];
            if ($duration <= 0) {
                continue;
            }
            if (!$this->isPositiveNumber($heartRateData[$i])) {
                continue;
            }
            if (!$this->isPositiveNumber($velocityData[$i])) {
                continue;
            }

            $segments[] = [
                'duration' => $duration,
                'heartRate' => (float) $heartRateData[$i],
                'speed' => (float) $velocityData[$i] * 60,
            ];
            $totalMovingDuration += $duration;
        }

        if ($totalMovingDuration <= 0) {
            return null;
        }

        $halfDuration = $totalMovingDuration / 2;
        $firstHalf = $this->emptyHalf();
        $secondHalf = $this->emptyHalf();
        $processedDuration = 0.0;

        foreach ($segments as $segment) {
            $remainingFirstHalfDuration = max(0, $halfDuration - $processedDuration);
            $firstHalfDuration = min($segment['duration'], $remainingFirstHalfDuration);
            $secondHalfDuration = $segment['duration'] - $firstHalfDuration;

            if ($firstHalfDuration > 0) {
                $this->addToHalf($firstHalf, $segment, $firstHalfDuration);
            }

            if ($secondHalfDuration > 0) {
                $this->addToHalf($secondHalf, $segment, $secondHalfDuration);
            }

            $processedDuration += $segment['duration'];
        }

        $firstEfficiencyFactor = $this->calculateEfficiencyFactor($firstHalf);
        $secondEfficiencyFactor = $this->calculateEfficiencyFactor($secondHalf);
        if (null === $firstEfficiencyFactor || null === $secondEfficiencyFactor || 0.0 === $firstEfficiencyFactor) {
            return null;
        }

        return (($firstEfficiencyFactor - $secondEfficiencyFactor) / $firstEfficiencyFactor) * 100;
    }

    private function isPositiveNumber(mixed $value): bool
    {
        return is_numeric($value) && (float) $value > 0;
    }

    /**
     * @return array{duration: float, speedSum: float, heartRateSum: float}
     */
    private function emptyHalf(): array
    {
        return [
            'duration' => 0.0,
            'speedSum' => 0.0,
            'heartRateSum' => 0.0,
        ];
    }

    /**
     * @param array{duration: float, speedSum: float, heartRateSum: float} $half
     * @param array{duration: float, heartRate: float, speed: float}       $segment
     */
    private function addToHalf(array &$half, array $segment, float $duration): void
    {
        $half['duration'] += $duration;
        $half['speedSum'] += $segment['speed'] * $duration;
        $half['heartRateSum'] += $segment['heartRate'] * $duration;
    }

    /**
     * @param array{duration: float, speedSum: float, heartRateSum: float} $half
     */
    private function calculateEfficiencyFactor(array $half): ?float
    {
        if ($half['duration'] <= 0 || $half['heartRateSum'] <= 0 || $half['speedSum'] <= 0) {
            return null;
        }

        return ($half['speedSum'] / $half['duration']) / ($half['heartRateSum'] / $half['duration']);
    }
}
