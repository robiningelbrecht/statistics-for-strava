<?php

declare(strict_types=1);

namespace App\Domain\Athlete\Weight;

use App\Infrastructure\ValueObject\Measurement\Mass\Gram;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class AthleteWeight
{
    private function __construct(
        private SerializableDateTime $on,
        private Gram $weightInGrams,
    ) {
    }

    public static function fromState(
        SerializableDateTime $on,
        Gram $weightInGrams,
    ): self {
        return new self(
            on: $on,
            weightInGrams: $weightInGrams
        );
    }

    public function getOn(): SerializableDateTime
    {
        return $this->on;
    }

    public function getWeightInGrams(): Gram
    {
        return $this->weightInGrams;
    }

    public function getWeightInKg(): Kilogram
    {
        return $this->getWeightInGrams()->toKilogram();
    }
}
