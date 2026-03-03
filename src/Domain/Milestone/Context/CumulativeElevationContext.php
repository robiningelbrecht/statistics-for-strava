<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class CumulativeElevationContext implements MilestoneContext
{
    public function __construct(
        private Meter|Foot $threshold,
    ) {
    }

    public function getThreshold(): Meter|Foot
    {
        return $this->threshold;
    }
}
