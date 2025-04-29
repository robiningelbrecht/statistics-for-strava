<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityWasDeleted;
use App\Domain\Strava\Segment\DeleteOrphanedSegments\DeleteOrphanedSegments;
use App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts\DeleteActivitySegmentEfforts;
use App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts\SegmentEffortsWereDeleted;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SegmentEffortActivityManager
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[AsEventListener]
    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $this->commandBus->dispatch(new DeleteActivitySegmentEfforts(
            $event->getActivityId())
        );
    }

    #[AsEventListener]
    public function reactToSegmentEffortsWereDeleted(SegmentEffortsWereDeleted $event): void
    {
        $this->commandBus->dispatch(new DeleteOrphanedSegments());
    }
}
