<?php

namespace App\Infrastructure\ValueObject\Measurement;

final class SimpleUnit implements Unit
{
    use ProvideMeasurementUnit;

    public function getSymbol(): string
    {
        return '';
    }
}
