<?php

declare(strict_types=1);

namespace App\Domain\Athlete\MaxHeartRate;

use App\Domain\Athlete\InvalidHeartRateFormula;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class MaxHeartRateFormulas
{
    /**
     * @param string|array<string, int> $maxHeartRateFormulaFromConfig
     */
    public function determineFormula(string|array $maxHeartRateFormulaFromConfig): MaxHeartRateFormula
    {
        if (is_string($maxHeartRateFormulaFromConfig)) {
            if (in_array(trim($maxHeartRateFormulaFromConfig), ['', '0'], true)) {
                throw new InvalidHeartRateFormula('MAX_HEART_RATE_FORMULA cannot be empty');
            }

            return match ($maxHeartRateFormulaFromConfig) {
                'arena' => new Arena(),
                'astrand' => new Astrand(),
                'fox' => new Fox(),
                'gellish' => new Gellish(),
                'nes' => new Nes(),
                'tanaka' => new Tanaka(),
                default => throw new InvalidHeartRateFormula(sprintf('Invalid MAX_HEART_RATE_FORMULA "%s" detected', $maxHeartRateFormulaFromConfig)),
            };
        }

        if ([] === $maxHeartRateFormulaFromConfig) {
            throw new InvalidHeartRateFormula('MAX_HEART_RATE_FORMULA date range cannot be empty');
        }

        $dateRangeBased = DateRangeBased::empty();
        foreach ($maxHeartRateFormulaFromConfig as $on => $maxHeartRate) {
            try {
                $dateRangeBased = $dateRangeBased->addRange(
                    on: SerializableDateTime::fromString($on),
                    maxHeartRate: $maxHeartRate
                );
            } catch (\DateMalformedStringException) {
                throw new InvalidHeartRateFormula(sprintf('Invalid date "%s" set in MAX_HEART_RATE_FORMULA', $on));
            }
        }

        return $dateRangeBased;
    }
}
