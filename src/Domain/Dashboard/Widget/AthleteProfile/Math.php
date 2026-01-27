<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\AthleteProfile;

final readonly class Math
{
    /**
     * @param array<int|float> $values
     */
    public static function median(array $values): float
    {
        if ([] === $values) {
            throw new \InvalidArgumentException('Cannot calculate median of empty array.');
        }

        sort($values, SORT_NUMERIC);

        $count = count($values);
        $middle = intdiv($count, 2);

        if (1 === $count % 2) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
