<?php

declare(strict_types=1);

namespace App\Domain\Activity\Lap;

use App\Domain\Activity\ActivityWasDeleted;
use App\Domain\Activity\Lap\DeleteActivityLaps\DeleteActivityLaps;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ActivityLapManager
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[AsEventListener]
    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $this->commandBus->dispatch(new DeleteActivityLaps($event->getActivityId()));
    }
}
