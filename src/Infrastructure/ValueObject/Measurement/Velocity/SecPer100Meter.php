<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class SecPer100Meter implements Pace
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return '100m';
    }

    public function toUnitSystem(UnitSystem $unitSystem): self
    {
        return $this;
    }
}
