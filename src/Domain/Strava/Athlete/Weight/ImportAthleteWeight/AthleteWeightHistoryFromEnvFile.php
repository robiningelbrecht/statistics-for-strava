<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\AthleteWeight;
use App\Domain\Strava\Athlete\Weight\AthleteWeights;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class AthleteWeightHistoryFromEnvFile
{
    private AthleteWeights $weights;

    /**
     * @param array<string, float> $weightsFromEnv
     */
    private function __construct(
        array $weightsFromEnv,
        private UnitSystem $unitSystem,
    ) {
        $this->weights = AthleteWeights::empty();

        foreach ($weightsFromEnv as $on => $weight) {
            $weightInGrams = Kilogram::from($weight)->toGram();
            if (UnitSystem::IMPERIAL === $this->unitSystem) {
                $weightInGrams = Pound::from($weight)->toGram();
            }

            try {
                $this->weights->add(AthleteWeight::fromState(
                    on: SerializableDateTime::fromString($on),
                    weightInGrams: $weightInGrams,
                ));
            } catch (\DateMalformedStringException) {
                throw new \InvalidArgumentException(sprintf('Invalid date "%s" set in ATHLETE_WEIGHT_HISTORY in .env file', $on));
            }
        }
    }

    public function getAll(): AthleteWeights
    {
        return $this->weights;
    }

    public static function fromString(string $values, UnitSystem $unitSystem): self
    {
        try {
            return new self(
                weightsFromEnv: Json::decode($values),
                unitSystem: $unitSystem
            );
        } catch (\JsonException) {
            throw new \InvalidArgumentException('Invalid ATHLETE_WEIGHT_HISTORY detected in .env file. Make sure the string is valid JSON');
        }
    }
}
