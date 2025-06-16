<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Lap\DeleteActivityLaps;

use App\Domain\Strava\Activity\Lap\ActivityLapRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivityLapsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityLapRepository $activityLapRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivityLaps);

        $this->activityLapRepository->deleteForActivity($command->getActivityId());
    }
}
