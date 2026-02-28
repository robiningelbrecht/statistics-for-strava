<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Infrastructure\ValueObject\Measurement\Time\Hour;

final readonly class CumulativeMovingTimeContext implements MilestoneContext
{
    public function __construct(
        private Hour $threshold,
        private Hour $totalMovingTime,
    ) {
    }

    public function getThreshold(): Hour
    {
        return $this->threshold;
    }

    public function getTotalMovingTime(): Hour
    {
        return $this->totalMovingTime;
    }
}
