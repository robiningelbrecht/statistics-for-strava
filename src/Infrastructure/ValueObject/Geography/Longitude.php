<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Number\FloatLiteral;

final readonly class Longitude extends FloatLiteral
{
    protected function guardValid(float $float): void
    {
        if (\abs($float) > 180) {
            throw new \InvalidArgumentException('Invalid longitude value: '.$float);
        }
    }
}
