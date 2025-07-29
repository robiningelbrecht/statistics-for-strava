<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class SecPerMile implements Pace
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'sec/mi';
    }

    public function toSecPerKm(): SecPerKm
    {
        return SecPerKm::from($this->value * Kilometer::FACTOR_TO_MILES);
    }

    public function toUnitSystem(UnitSystem $unitSystem): Pace
    {
        if (UnitSystem::IMPERIAL === $unitSystem) {
            return $this;
        }

        return $this->toSecPerKm();
    }
}
