<?php

declare(strict_types=1);

namespace App\Domain\Activity;

trait ProvideSteppedValue
{
    private function findClosestSteppedValue(int $min, int $max, int $step, int|float $target): int
    {
        $stepsFromMin = round(($target - $min) / $step);
        $closest = (int) round($min + ($stepsFromMin * $step));

        if ($closest < $min) {
            return $min;
        }
        if ($closest > $max) {
            return $max;
        }

        return $closest;
    }
}
