<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;

final readonly class CumulativeDistanceContext implements MilestoneContext
{
    public function __construct(
        public Kilometer $threshold,
        public Kilometer $totalDistance,
    ) {
    }
}
