<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Time;

use App\Infrastructure\ValueObject\Measurement\ProvideMeasurementUnit;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class Minute implements Unit
{
    use ProvideMeasurementUnit;

    public function getSymbol(): string
    {
        return 'min';
    }
}
