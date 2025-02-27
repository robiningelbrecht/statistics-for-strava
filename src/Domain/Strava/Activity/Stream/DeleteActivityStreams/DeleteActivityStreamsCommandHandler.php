<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\DeleteActivityStreams;

use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;

final readonly class DeleteActivityStreamsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivityStreams);

        $streams = $this->activityStreamRepository->findByActivityId($command->getActivityId());
        if ($streams->isEmpty()) {
            return;
        }

        foreach ($streams as $stream) {
            $this->activityStreamRepository->delete($stream);
        }
    }
}
