<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Metric;
use App\Infrastructure\ValueObject\Measurement\ProvideMeasurementUnit;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class KmPerHour implements Unit, Metric, Velocity
{
    use ProvideMeasurementUnit;

    public function getSymbol(): string
    {
        return 'km/h';
    }

    public function toMph(): MilesPerHour
    {
        return MilesPerHour::from($this->value * Kilometer::FACTOR_TO_MILES);
    }

    public function toMetersPerSecond(): MetersPerSecond
    {
        return MetersPerSecond::from(round($this->value * 0.2777777778, 3));
    }

    public function toImperial(): Unit
    {
        return $this->toMph();
    }

    public function toUnitSystem(UnitSystem $unitSystem): KmPerHour|MilesPerHour
    {
        if (UnitSystem::METRIC === $unitSystem) {
            return $this;
        }

        return $this->toMph();
    }
}
