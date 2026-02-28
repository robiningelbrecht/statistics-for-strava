<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;

final readonly class CumulativeMovingTimeContext implements MilestoneContext
{
    public function __construct(
        public Hour $threshold,
        public Hour $totalMovingTime,
    ) {
    }
}
