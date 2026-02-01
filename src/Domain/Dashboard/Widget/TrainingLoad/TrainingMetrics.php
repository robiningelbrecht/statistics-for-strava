<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingLoad;

final class TrainingMetrics
{
    /** @var array<string, int|float> */
    private array $atlValues = [];
    /** @var array<string, int|float> */
    private array $ctlValues = [];
    /** @var array<string, int|float> */
    private array $tsbValues = [];
    /** @var array<string, int|float|null> */
    private array $trimpValues = [];
    /** @var array<string, int|float|null> */
    private array $monotonyValues = [];
    /** @var array<string, int|float|null> */
    private array $strainValues = [];
    /** @var array<string, int|float> */
    private array $acRatioValues = [];

    private function __construct(
        /** @var array<string, int> */
        private readonly array $intensities,
    ) {
        $this->buildMetrics();
    }

    /**
     * @param array<string, int> $intensities
     */
    public static function create(array $intensities): TrainingMetrics
    {
        return new self($intensities);
    }

    private function buildMetrics(): void
    {
        $alphaATL = 1 - exp(-1 / 7);
        $alphaCTL = 1 - exp(-1 / TrainingLoadChart::ROLLING_WINDOW_TO_CALCULATE_METRICS_AGAINST);

        $altValues = $ctlValues = $tsbValues = $trimpValues = $monotonyValues = $strainValues = $acRatioValues = [];

        $delta = 0;
        foreach ($this->intensities as $intensity) {
            $trimpValues[$delta] = $intensity;

            if (0 === $delta) {
                $altValues[$delta] = $intensity;
                $ctlValues[$delta] = $intensity;
                $tsbValues[$delta] = 0;
            } else {
                $altValues[$delta] = ($intensity * $alphaATL) + ($altValues[$delta - 1] * (1 - $alphaATL));
                $ctlValues[$delta] = ($intensity * $alphaCTL) + ($ctlValues[$delta - 1] * (1 - $alphaCTL));
                $tsbValues[$delta] = $ctlValues[$delta] - $altValues[$delta];
            }

            if ($delta >= 6) { // Day 6 = first full week
                $weekLoads = array_slice($this->intensities, $delta - 6, 7);
                $sum = array_sum($weekLoads);
                $avg = $sum / 7;
                $std = $this->standardDeviation($weekLoads);

                $monotonyValues[$delta] = $std > 0 ? $avg / $std : 0;
                $strainValues[$delta] = $sum * $monotonyValues[$delta];
            } else {
                $monotonyValues[$delta] = null;
                $strainValues[$delta] = null;
            }

            if (0 == $ctlValues[$delta]) {
                $acRatioValues[$delta] = 0;
            } else {
                $acRatioValues[$delta] = round($altValues[$delta] / $ctlValues[$delta], 2);
            }

            ++$delta;
        }

        $intensityKeys = array_keys($this->intensities);
        // Round numbers when all calculating is done and combine with original keys.
        $this->acRatioValues = array_combine($intensityKeys, $acRatioValues);
        $this->atlValues = array_combine($intensityKeys, array_map(fn (int|float $value): float => round($value, 1), $altValues));
        $this->ctlValues = array_combine($intensityKeys, array_map(fn (int|float $value): float => round($value, 1), $ctlValues));
        $this->tsbValues = array_combine($intensityKeys, array_map(fn (int|float $value): float => round($value, 1), $tsbValues));
        // Apply rounding/casting to the daily trimp values
        $this->trimpValues = array_combine($intensityKeys, array_map(fn (int|float|null $value): ?int => null === $value ? null : (int) round($value), $trimpValues));
        $this->strainValues = array_combine($intensityKeys, array_map(fn (int|float|null $value): ?int => null === $value ? null : (int) round($value), $strainValues));
        $this->monotonyValues = array_combine($intensityKeys, array_map(fn (int|float|null $value): ?float => null === $value ? null : round($value, 2), $monotonyValues));
    }

    /**
     * @return array<int, int|float>
     */
    public function getAtlValuesForXLastDays(int $numberOfDays): array
    {
        return array_values(array_slice($this->atlValues, -$numberOfDays));
    }

    public function getCurrentAtl(): ?float
    {
        if (empty($this->atlValues)) {
            return null;
        }

        return end($this->atlValues);
    }

    /**
     * @return array<int, int|float>
     */
    public function getCtlValuesForXLastDays(int $numberOfDays): array
    {
        return array_values(array_slice($this->ctlValues, -$numberOfDays));
    }

    public function getCurrentCtl(): ?float
    {
        if (empty($this->ctlValues)) {
            return null;
        }

        return end($this->ctlValues);
    }

    /**
     * @return array<int, int|float>
     */
    public function getTsbValuesForXLastDays(int $numberOfDays): array
    {
        return array_values(array_slice($this->tsbValues, -$numberOfDays));
    }

    public function getCurrentTsb(): ?float
    {
        if (empty($this->tsbValues)) {
            return null;
        }

        return end($this->tsbValues);
    }

    /**
     * @return array<int, int|float|null>
     */
    public function getTrimpValuesForXLastDays(int $numberOfDays): array
    {
        return array_values(array_slice($this->trimpValues, -$numberOfDays));
    }

    public function getWeeklyTrimp(): ?int
    {
        if (count($this->trimpValues) < 7) {
            return null;
        }

        $lastSevenDaysTrimp = array_slice($this->trimpValues, -7);

        return (int) array_sum($lastSevenDaysTrimp);
    }

    public function getCurrentMonotony(): ?float
    {
        if (empty($this->monotonyValues)) {
            return null;
        }

        return end($this->monotonyValues);
    }

    public function getCurrentStrain(): ?float
    {
        if (empty($this->strainValues)) {
            return null;
        }

        return end($this->strainValues);
    }

    public function getCurrentAcRatio(): ?float
    {
        if (empty($this->acRatioValues)) {
            return null;
        }

        return end($this->acRatioValues);
    }

    /**
     * Returns a 7-day forecast assuming zero training load (rest days).
     * Shows projected TSB and A:C Ratio recovery, plus days until healthy ranges.
     *
     * @return array{
     *     days: array<int, array{day: int, tsb: float, acRatio: float, tsbStatus: string, acRatioStatus: string}>,
     *     daysUntilTsbHealthy: int|null,
     *     daysUntilAcRatioHealthy: int|null
     * }
     */
    public function getForecast(): array
    {
        $alphaATL = 1 - exp(-1 / 7);
        $alphaCTL = 1 - exp(-1 / TrainingLoadChart::ROLLING_WINDOW_TO_CALCULATE_METRICS_AGAINST);

        $currentAtl = $this->getCurrentAtl() ?? 0;
        $currentCtl = $this->getCurrentCtl() ?? 0;

        $days = [];
        $daysUntilTsbHealthy = null;
        $daysUntilAcRatioHealthy = null;

        $atl = $currentAtl;
        $ctl = $currentCtl;

        for ($day = 1; $day <= 7; ++$day) {
            // Simulate zero training load for this day
            $atl = $atl * (1 - $alphaATL);
            $ctl = $ctl * (1 - $alphaCTL);
            $tsb = round($ctl - $atl, 1);
            $acRatio = $ctl > 0 ? round($atl / $ctl, 2) : 0;

            // Determine TSB status
            if ($tsb > 25) {
                $tsbStatus = 'detraining';
            } elseif ($tsb > 10) {
                $tsbStatus = 'fresh';
            } elseif ($tsb > 0) {
                $tsbStatus = 'slightly_fresh';
            } elseif ($tsb > -10) {
                $tsbStatus = 'neutral';
            } elseif ($tsb > -30) {
                $tsbStatus = 'fatigued';
            } else {
                $tsbStatus = 'over_fatigued';
            }

            // Determine A:C Ratio status
            if ($acRatio >= 0.8 && $acRatio <= 1.3) {
                $acRatioStatus = 'optimal';
            } elseif ($acRatio > 1.3) {
                $acRatioStatus = 'high_risk';
            } else {
                $acRatioStatus = 'low';
            }

            // Track when metrics reach healthy ranges
            if (null === $daysUntilTsbHealthy && $tsb > 0) {
                $daysUntilTsbHealthy = $day;
            }
            if (null === $daysUntilAcRatioHealthy && $acRatio >= 0.8 && $acRatio <= 1.3) {
                $daysUntilAcRatioHealthy = $day;
            }

            $days[] = [
                'day' => $day,
                'tsb' => $tsb,
                'acRatio' => $acRatio,
                'tsbStatus' => $tsbStatus,
                'acRatioStatus' => $acRatioStatus,
            ];
        }

        return [
            'days' => $days,
            'daysUntilTsbHealthy' => $daysUntilTsbHealthy,
            'daysUntilAcRatioHealthy' => $daysUntilAcRatioHealthy,
        ];
    }

    /**
     * @param array<string, int|float> $values
     */
    private function standardDeviation(array $values): float
    {
        $count = count($values);
        if (0 === $count) {
            return 0.0;
        }
        $mean = array_sum($values) / $count;
        $sumSquares = 0;
        foreach ($values as $v) {
            $sumSquares += ($v - $mean) ** 2;
        }

        return sqrt($sumSquares / $count);
    }
}
