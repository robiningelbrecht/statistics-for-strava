<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class DeleteActivitySegmentEfforts extends DomainCommand
{
    public function __construct(
        private ActivityId $activityId,
    ) {
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }
}
