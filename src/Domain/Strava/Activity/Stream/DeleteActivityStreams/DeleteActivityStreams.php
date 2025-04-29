<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\DeleteActivityStreams;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class DeleteActivityStreams extends DomainCommand
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
