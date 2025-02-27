<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\DeleteActivityStreams;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\CQRS\DomainCommand;

final class DeleteActivityStreams extends DomainCommand
{
    public function __construct(
        private readonly ActivityId $activityId,
    ) {
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }
}
