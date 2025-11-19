<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Unit;

interface Weight extends Unit
{
    public function toKilogram(): Kilogram;
}
