<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityWasDeleted;
use App\Domain\Activity\BestEffort\DeleteActivityBestEfforts\DeleteActivityBestEfforts;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ActivityBestEffortManager
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[AsEventListener]
    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $this->commandBus->dispatch(new DeleteActivityBestEfforts($event->getActivityId()));
    }
}
