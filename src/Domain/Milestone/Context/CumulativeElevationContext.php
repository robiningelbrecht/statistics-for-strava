<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;
use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class CumulativeElevationContext implements MilestoneContext
{
    public function __construct(
        public Meter|Foot $threshold,
        public Meter|Foot $totalElevation,
    ) {
    }
}
