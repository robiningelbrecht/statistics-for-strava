<?php

namespace App\Application\Import\DeleteActivities;

use App\Domain\Activity\ActivityRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivities);

        // STEAMS.
        $streams = $this->activityStreamRepository->findByActivityId($command->getActivityId());
        if ($streams->isEmpty()) {
            return;
        }

        foreach ($streams as $stream) {
            $this->activityStreamRepository->delete($stream);
        }

        // SEGMENTS.
        $this->segmentEffortRepository->deleteForActivity($command->getActivityId());
        $this->segmentRepository->deleteOrphaned();

        $this->activitySplitRepository->deleteForActivity($command->getActivityId());
        $this->activityLapRepository->deleteForActivity($command->getActivityId());
        $this->activityBestEffortRepository->deleteForActivity($command->getActivityId());

        $this->activityRepository->delete();
    }
}
