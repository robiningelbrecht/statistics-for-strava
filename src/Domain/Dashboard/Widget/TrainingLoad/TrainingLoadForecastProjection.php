<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingLoad;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class TrainingLoadForecastProjection
{
    /** @var array<int, array{day: SerializableDateTime, tsb: TSB, acRatio: AcRatio}> */
    private array $forecast = [];
    private ?int $daysUntilTsbHealthy = null;
    private ?int $daysUntilAcRatioHealthy = null;

    private function __construct(
        private readonly TrainingMetrics $trainingMetrics,
        private readonly SerializableDateTime $now,
    ) {
        $this->buildForecast();
    }

    public static function create(
        TrainingMetrics $metrics,
        SerializableDateTime $now,
    ): self {
        return new self(
            trainingMetrics: $metrics,
            now: $now,
        );
    }

    private function buildForecast(): void
    {
        $alphaATL = 1 - exp(-1 / 7);
        $alphaCTL = 1 - exp(-1 / TrainingLoadChart::ROLLING_WINDOW_TO_CALCULATE_METRICS_AGAINST);

        $currentAtl = $this->trainingMetrics->getCurrentAtl() ?? 0;
        $currentCtl = $this->trainingMetrics->getCurrentCtl() ?? 0;

        $atl = $currentAtl;
        $ctl = $currentCtl;

        $numberOfDaysToForecast = 7;
        for ($day = 1; $day <= $numberOfDaysToForecast; ++$day) {
            $atl *= 1 - $alphaATL;
            $ctl *= 1 - $alphaCTL;
            $tsb = round($ctl - $atl, 1);
            $acRatio = $ctl > 0 ? round($atl / $ctl, 2) : 0;

            if (null === $this->daysUntilTsbHealthy && $tsb > 0) {
                $this->daysUntilTsbHealthy = $day;
            }
            if (null === $this->daysUntilAcRatioHealthy && $acRatio >= 0.8 && $acRatio <= 1.3) {
                $this->daysUntilAcRatioHealthy = $day;
            }

            $this->forecast[] = [
                'day' => $this->now->modify(sprintf('+ %d days', $day)),
                'tsb' => TSB::of($tsb),
                'acRatio' => AcRatio::of($acRatio),
            ];
        }
    }

    /**
     * @return array<int, array{day: SerializableDateTime, tsb: TSB, acRatio: AcRatio}>
     */
    public function getProjection(): array
    {
        return $this->forecast;
    }

    public function getDaysUntilTsbHealthy(): ?int
    {
        return $this->daysUntilTsbHealthy;
    }

    public function getDaysUntilAcRatioHealthy(): ?int
    {
        return $this->daysUntilAcRatioHealthy;
    }
}
