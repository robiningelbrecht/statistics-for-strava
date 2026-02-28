<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Activity\SportType\SportType;

final readonly class FirstContext implements MilestoneContext
{
    public function __construct(
        private SportType $sportType,
        private string $activityName,
    ) {
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function getActivityName(): string
    {
        return $this->activityName;
    }
}
