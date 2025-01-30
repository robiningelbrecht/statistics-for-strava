<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Metric;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class KmPerHour implements Unit, Metric
{
    use MeasurementFromFloat;

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
}
