<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

final class SimpleUnit implements Unit
{
    use ProvideMeasurementUnit;

    public function getSymbol(): string
    {
        return '';
    }
}
