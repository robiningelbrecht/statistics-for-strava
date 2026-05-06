<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

interface GapAdjustmentModel
{
    public function adjustmentFactor(float $grade): float;
}
