<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Length;

use App\Infrastructure\ValueObject\Measurement\Unit;

interface ConvertableToMeter extends Unit
{
    public function toMeter(): Meter;
}
