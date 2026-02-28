<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

final readonly class StreakContext implements MilestoneContext
{
    public function __construct(
        private int $days,
    ) {
    }

    public function getDays(): int
    {
        return $this->days;
    }
}
