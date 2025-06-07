<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class AthleteWeightHistory
{
    /** @var AthleteWeight[] */
    private array $weights;

    /**
     * @param array<string, float> $weightsFromEnv
     */
    private function __construct(
        array $weightsFromEnv,
        private readonly UnitSystem $unitSystem,
    ) {
        $this->weights = [];

        foreach ($weightsFromEnv as $on => $weight) {
            $weightInGrams = Kilogram::from($weight)->toGram();
            if (UnitSystem::IMPERIAL === $this->unitSystem) {
                $weightInGrams = Pound::from($weight)->toGram();
            }

            try {
                $onDate = SerializableDateTime::fromString($on);
                $this->weights[$onDate->getTimestamp()] = AthleteWeight::fromState(
                    on: $onDate,
                    weightInGrams: $weightInGrams,
                );
            } catch (\DateMalformedStringException) {
                throw new \InvalidArgumentException(sprintf('Invalid date "%s" set for athlete weightHistory in config.yaml file', $on));
            }
        }

        krsort($this->weights);
    }

    public function find(SerializableDateTime $on): AthleteWeight
    {
        $on = SerializableDateTime::fromString($on->format('Y-m-d'));
        /** @var AthleteWeight $athleteWeight */
        foreach ($this->weights as $athleteWeight) {
            if ($on->isAfterOrOn($athleteWeight->getOn())) {
                return $athleteWeight;
            }
        }

        throw new EntityNotFound(sprintf('AthleteWeight for date "%s" not found', $on));
    }

    /**
     * @param array<string, float> $values
     */
    public static function fromArray(array $values, UnitSystem $unitSystem): self
    {
        return new self(
            weightsFromEnv: $values,
            unitSystem: $unitSystem
        );
    }
}
