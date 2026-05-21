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
    public function calculateForRun(
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

    /**
     * @param array<int, mixed> $timeData
     * @param array<int, mixed> $movingData
     * @param array<int, mixed> $heartRateData
     * @param array<int, mixed> $powerData
     */
    public function calculateForRide(
        array $timeData,
        array $movingData,
        array $heartRateData,
        array $powerData,
    ): ?float {
        $segments = [];
        $totalMovingDuration = 0.0;
        $count = min(count($timeData), count($movingData), count($heartRateData), count($powerData));

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
            if (!$this->isPositiveNumber($powerData[$i])) {
                continue;
            }

            $segments[] = [
                'duration' => $duration,
                'heartRate' => (float) $heartRateData[$i],
                'power' => (float) $powerData[$i],
            ];
            $totalMovingDuration += $duration;
        }

        if ($totalMovingDuration <= 0) {
            return null;
        }

        $halfDuration = $totalMovingDuration / 2;
        $firstHalf = $this->emptyPowerHalf();
        $secondHalf = $this->emptyPowerHalf();
        $processedDuration = 0.0;

        foreach ($segments as $segment) {
            $remainingFirstHalfDuration = max(0, $halfDuration - $processedDuration);
            $firstHalfDuration = min($segment['duration'], $remainingFirstHalfDuration);
            $secondHalfDuration = $segment['duration'] - $firstHalfDuration;

            if ($firstHalfDuration > 0) {
                $this->addToPowerHalf($firstHalf, $segment, $firstHalfDuration);
            }

            if ($secondHalfDuration > 0) {
                $this->addToPowerHalf($secondHalf, $segment, $secondHalfDuration);
            }

            $processedDuration += $segment['duration'];
        }

        $firstEfficiencyFactor = $this->calculatePowerEfficiencyFactor($firstHalf);
        $secondEfficiencyFactor = $this->calculatePowerEfficiencyFactor($secondHalf);
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

    /**
     * @return array{duration: float, heartRateSum: float, powers: array<int, float>}
     */
    private function emptyPowerHalf(): array
    {
        return [
            'duration' => 0.0,
            'heartRateSum' => 0.0,
            'powers' => [],
        ];
    }

    /**
     * @param array{duration: float, heartRateSum: float, powers: array<int, float>} $half
     * @param array{duration: float, heartRate: float, power: float}                 $segment
     */
    private function addToPowerHalf(array &$half, array $segment, float $duration): void
    {
        $half['duration'] += $duration;
        $half['heartRateSum'] += $segment['heartRate'] * $duration;
        $half['powers'][] = $segment['power'];
    }

    /**
     * @param array{duration: float, heartRateSum: float, powers: array<int, float>} $half
     */
    private function calculatePowerEfficiencyFactor(array $half): ?float
    {
        if ($half['duration'] <= 0 || $half['heartRateSum'] <= 0) {
            return null;
        }

        $normalizedPower = $this->calculateNormalizedPower($half['powers']);
        if (null === $normalizedPower || $normalizedPower <= 0) {
            return null;
        }

        return $normalizedPower / ($half['heartRateSum'] / $half['duration']);
    }

    /**
     * @param array<int, float> $powerData
     */
    private function calculateNormalizedPower(array $powerData): ?float
    {
        if (count($powerData) < 30) {
            return null;
        }

        $windowSize = 30;
        $movingAvg = [];
        $counter = count($powerData);

        for ($i = $windowSize - 1; $i < $counter; ++$i) {
            $window = array_slice($powerData, $i - $windowSize + 1, $windowSize);
            $avg = array_sum($window) / $windowSize;
            $movingAvg[] = $avg ** 4;
        }

        return (array_sum($movingAvg) / count($movingAvg)) ** 0.25;
    }
}
