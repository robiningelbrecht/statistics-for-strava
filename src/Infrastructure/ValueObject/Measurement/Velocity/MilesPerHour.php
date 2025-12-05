<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\Imperial;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\ProvideMeasurementUnit;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class MilesPerHour implements Unit, Imperial, Velocity
{
    use ProvideMeasurementUnit;

    public function getSymbol(): string
    {
        return 'mph';
    }

    public function toKmH(): KmPerHour
    {
        return KmPerHour::from($this->value * Mile::FACTOR_TO_KM);
    }

    public function toMetric(): Unit
    {
        return $this->toKmH();
    }
}
