<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Number\PositiveInteger;

final readonly class ZoomLevel extends PositiveInteger
{
    private const int MIN = 1;
    private const int MAX = 18;

    protected function validate(int $value): void
    {
        if ($value >= self::MIN && $value <= self::MAX) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('ZoomLevel must be a number between %d and %d, got %d', self::MIN, self::MAX, $value));
    }
}
