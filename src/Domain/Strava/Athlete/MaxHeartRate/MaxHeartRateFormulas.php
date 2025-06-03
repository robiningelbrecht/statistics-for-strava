<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class MaxHeartRateFormulas
{
    /**
     * @param string|array<string, int> $maxHeartRateFormulaFromConfig
     */
    public function determineFormula(string|array $maxHeartRateFormulaFromConfig): MaxHeartRateFormula
    {
        if (is_string($maxHeartRateFormulaFromConfig)) {
            if (empty(trim($maxHeartRateFormulaFromConfig))) {
                throw new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA cannot be empty');
            }

            return match ($maxHeartRateFormulaFromConfig) {
                'arena' => new Arena(),
                'astrand' => new Astrand(),
                'fox' => new Fox(),
                'gellish' => new Gellish(),
                'nes' => new Nes(),
                'tanaka' => new Tanaka(),
                default => throw new InvalidMaxHeartRateFormula(sprintf('Invalid MAX_HEART_RATE_FORMULA "%s" detected', $maxHeartRateFormulaFromConfig)),
            };
        }

        if (empty($maxHeartRateFormulaFromConfig)) {
            throw new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA date range cannot be empty');
        }

        $dateRangeBased = DateRangeBased::empty();
        foreach ($maxHeartRateFormulaFromConfig as $on => $maxHeartRate) {
            try {
                $dateRangeBased = $dateRangeBased->addRange(
                    on: SerializableDateTime::fromString($on),
                    maxHeartRate: $maxHeartRate
                );
            } catch (\DateMalformedStringException) {
                throw new InvalidMaxHeartRateFormula(sprintf('Invalid date "%s" set in MAX_HEART_RATE_FORMULA', $on));
            }
        }

        return $dateRangeBased;
    }
}
