<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Time;

use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class Seconds implements Unit
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 's';
    }

    public function toHour(): Hour
    {
        return Hour::from($this->value / 3600);
    }

    public function toMinute(): Minute
    {
        return Minute::from($this->value / 60);
    }
}
