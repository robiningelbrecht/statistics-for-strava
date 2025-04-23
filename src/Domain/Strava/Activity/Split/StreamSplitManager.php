<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\ActivityWasDeleted;
use App\Domain\Strava\Activity\Split\DeleteActivitySplits\DeleteActivitySplits;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class StreamSplitManager
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[AsEventListener]
    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $this->commandBus->dispatch(new DeleteActivitySplits($event->getActivityId()));
    }
}
