<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\MaxHeartRate;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class MaxHeartRateFormulas
{
    public function determineFormula(string $maxHeartRateFormulaFromEnvFile): MaxHeartRateFormula
    {
        if (empty(trim($maxHeartRateFormulaFromEnvFile))) {
            throw new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA cannot be empty');
        }

        if ($matchedFormula = match ($maxHeartRateFormulaFromEnvFile) {
            'arena' => new Arena(),
            'astrand' => new Astrand(),
            'fox' => new Fox(),
            'gellish' => new Gellish(),
            'nes' => new Nes(),
            'tanaka' => new Tanaka(),
            default => null,
        }) {
            return $matchedFormula;
        }

        try {
            $heartRateFormula = Json::decode($maxHeartRateFormulaFromEnvFile);
            if (!is_array($heartRateFormula)) {
                throw new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA invalid date range');
            }
            if (empty($heartRateFormula)) {
                throw new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA date range cannot be empty');
            }

            $dateRangeBased = DateRangeBased::empty();
            foreach ($heartRateFormula as $on => $maxHeartRate) {
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
        } catch (\JsonException) {
        }

        throw new InvalidMaxHeartRateFormula(sprintf('Invalid MAX_HEART_RATE_FORMULA "%s" detected', $maxHeartRateFormulaFromEnvFile));
    }
}
