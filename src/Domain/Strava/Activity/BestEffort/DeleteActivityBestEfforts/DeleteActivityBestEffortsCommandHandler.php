<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort\DeleteActivityBestEfforts;

use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivityBestEffortsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityBestEffortRepository $activityBestEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivityBestEfforts);

        $this->activityBestEffortRepository->deleteForActivity($command->getActivityId());
    }
}
