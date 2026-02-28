<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;

final readonly class ActivityCountContext implements MilestoneContext
{
    public function __construct(
        public int $threshold,
        public int $totalCount,
    ) {
    }
}
