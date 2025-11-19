<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Metric;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class Kilogram implements Weight, Metric
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'kg';
    }

    public function toPound(): Pound
    {
        return Pound::from($this->value * 2.20462);
    }

    public function toImperial(): Unit
    {
        return $this->toPound();
    }

    public function toKilogram(): Kilogram
    {
        return $this;
    }
}
