<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Number\FloatLiteral;

final readonly class Latitude extends FloatLiteral
{
    protected function guardValid(float $float): void
    {
        if (\abs($float) > 90) {
            throw new \InvalidArgumentException('Invalid latitude value: '.$float);
        }
    }
}
