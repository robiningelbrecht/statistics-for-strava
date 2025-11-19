<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Imperial;
use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class Pound implements Weight, Imperial
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'lb';
    }

    public function toMetric(): Unit
    {
        return $this->toKilogram();
    }

    public function toKilogram(): Kilogram
    {
        return Kilogram::from($this->value * 0.45359237);
    }
}
