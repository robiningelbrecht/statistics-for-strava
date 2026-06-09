<?php

declare(strict_types=1);

namespace App\Domain\Activity;

final readonly class Math
{
    private const float EARTH_RADIUS_IN_METERS = 6371000.0;

    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2.0) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLon / 2.0) ** 2;

        return self::EARTH_RADIUS_IN_METERS * 2.0 * atan2(sqrt($a), sqrt(1.0 - self::clamp($a, 0.0, 1.0)));
    }

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

    /**
     * @param array<int|float> $values
     *
     * @return array<int|float>
     */
    public static function movingAverage(array $values, int $windowSize): array
    {
        $count = count($values);
        if (0 === $count || $windowSize < 1) {
            return $values;
        }
        $half = intdiv($windowSize, 2);

        $smoothed = [];
        for ($i = 0; $i < $count; ++$i) {
            $start = max(0, $i - $half);
            $end = min($count - 1, $i + $half);
            $sum = 0.0;
            for ($j = $start; $j <= $end; ++$j) {
                $sum += $values[$j];
            }
            $smoothed[] = $sum / ($end - $start + 1);
        }

        return $smoothed;
    }

    public static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    public static function semicirclesToDegrees(float $semicircles): float
    {
        return $semicircles * 180 / 2 ** 31;
    }

    /**
     * @param array<int|float|null> $values
     */
    public static function average(array $values): ?int
    {
        $numbers = array_filter($values, static fn (mixed $v): bool => null !== $v);
        if ([] === $numbers) {
            return null;
        }

        return (int) round(array_sum($numbers) / count($numbers));
    }

    /**
     * @param array<int|float|null> $values
     */
    public static function max(array $values): ?int
    {
        $numbers = array_filter($values, static fn (mixed $v): bool => null !== $v);
        if ([] === $numbers) {
            return null;
        }

        return (int) max($numbers);
    }
}
