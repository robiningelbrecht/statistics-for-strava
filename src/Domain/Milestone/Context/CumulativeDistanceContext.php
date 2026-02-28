<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;

final readonly class CumulativeDistanceContext implements MilestoneContext
{
    public function __construct(
        public Kilometer|Mile $threshold,
        public Kilometer|Mile $totalDistance,
    ) {
    }
}
