<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

interface Imperial
{
    public function toMetric(): Unit;
}
