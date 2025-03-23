<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split\DeleteActivitySplits;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\CQRS\DomainCommand;

final readonly class DeleteActivitySplits extends DomainCommand
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
