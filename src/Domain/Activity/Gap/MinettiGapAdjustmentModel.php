<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

final readonly class MinettiGapAdjustmentModel implements GapAdjustmentModel
{
    private const float FLAT_METABOLIC_COST = 3.6;

    public function adjustmentFactor(float $grade): float
    {
        $grade2 = $grade * $grade;
        $grade3 = $grade2 * $grade;
        $grade4 = $grade3 * $grade;
        $grade5 = $grade4 * $grade;

        $metabolicCost = 155.4 * $grade5
            - 30.4 * $grade4
            - 43.3 * $grade3
            + 46.3 * $grade2
            + 19.5 * $grade
            + self::FLAT_METABOLIC_COST;

        return max(0.1, $metabolicCost / self::FLAT_METABOLIC_COST);
    }
}
