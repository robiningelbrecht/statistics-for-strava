<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\MilestoneContext;

final readonly class FirstContext implements MilestoneContext
{
    public function __construct(
        public SportType $sportType,
        public string $activityName,
    ) {
    }
}
