<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityWasDeleted;
use App\Domain\Activity\Stream\DeleteActivityStreams\DeleteActivityStreams;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class StreamActivityManager
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[AsEventListener]
    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $this->commandBus->dispatch(new DeleteActivityStreams($event->getActivityId()));
    }
}
