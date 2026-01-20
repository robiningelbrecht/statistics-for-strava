<?php

namespace App\Application\Import\DeleteActivitiesMarkedForDeletion;

use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivitySummaryRepository;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivitiesMarkedForDeletionCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityIdRepository $activityIdRepository,
        private ActivitySummaryRepository $activitySummaryRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private SegmentRepository $segmentRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityLapRepository $activityLapRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivitiesMarkedForDeletion);

        $activityIdsToDelete = $this->activityIdRepository->findMarkedForDeletion();
        if ($activityIdsToDelete->isEmpty()) {
            $command->getOutput()->writeln('No activities marked for deletion...');

            return;
        }

        $command->getOutput()->writeln('Deleting activities...');

        foreach ($activityIdsToDelete as $activityId) {
            $activity = $this->activitySummaryRepository->find($activityId);

            $this->activityStreamRepository->deleteForActivity($activityId);
            $this->segmentEffortRepository->deleteForActivity($activityId);
            $this->segmentRepository->deleteOrphaned();
            $this->activitySplitRepository->deleteForActivity($activityId);
            $this->activityLapRepository->deleteForActivity($activityId);
            $this->activityBestEffortRepository->deleteForActivity($activityId);
            $this->activityWithRawDataRepository->delete($activityId);

            $command->getOutput()->writeln(sprintf(
                '  => Activity "%s - %s" deleted',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'))
            );
        }
    }
}
