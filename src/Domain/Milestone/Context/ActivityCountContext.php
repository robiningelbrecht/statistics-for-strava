<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

final readonly class ActivityCountContext implements MilestoneContext
{
    public function __construct(
        private int $threshold,
        private int $totalCount,
    ) {
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}
