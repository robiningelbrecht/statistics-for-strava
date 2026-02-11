<?php

namespace App\Domain\Athlete\RestingHeartRate;

use App\Domain\Athlete\InvalidHeartRateFormula;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class RestingHeartRateFormulas
{
    /**
     * @param string|array<string, int>|int $restingHeartRateFormulaFromConfig
     */
    public function determineFormula(string|array|int $restingHeartRateFormulaFromConfig): RestingHeartRateFormula
    {
        if (is_int($restingHeartRateFormulaFromConfig)) {
            return new Fixed($restingHeartRateFormulaFromConfig);
        }

        if (is_string($restingHeartRateFormulaFromConfig)) {
            if (ctype_digit($restingHeartRateFormulaFromConfig) && '0' !== $restingHeartRateFormulaFromConfig[0]) {
                return new Fixed((int) $restingHeartRateFormulaFromConfig);
            }

            if ('heuristicAgeBased' !== $restingHeartRateFormulaFromConfig) {
                throw new InvalidHeartRateFormula(sprintf('Invalid RESTING_HEART_RATE_FORMULA "%s" detected', $restingHeartRateFormulaFromConfig));
            }

            return new HeuristicAgeBased();
        }

        if ([] === $restingHeartRateFormulaFromConfig) {
            throw new InvalidHeartRateFormula('RESTING_HEART_RATE_FORMULA date range cannot be empty');
        }

        $dateRangeBased = DateRangeBased::empty();
        foreach ($restingHeartRateFormulaFromConfig as $on => $maxHeartRate) {
            try {
                $dateRangeBased = $dateRangeBased->addRange(
                    on: SerializableDateTime::fromString($on),
                    maxHeartRate: $maxHeartRate
                );
            } catch (\DateMalformedStringException) {
                throw new InvalidHeartRateFormula(sprintf('Invalid date "%s" set in RESTING_HEART_RATE_FORMULA', $on));
            }
        }

        return $dateRangeBased;
    }
}
