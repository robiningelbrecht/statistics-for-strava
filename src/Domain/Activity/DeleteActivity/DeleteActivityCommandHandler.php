<?php

declare(strict_types=1);

namespace App\Domain\Activity\DeleteActivity;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivityCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private SegmentRepository $segmentRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityLapRepository $activityLapRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivity);

        $activityId = $command->getActivityId();

        $this->activityStreamRepository->deleteForActivity($activityId);
        $this->activityStreamMetricRepository->deleteForActivity($activityId);
        $this->segmentEffortRepository->deleteForActivity($activityId);
        $this->segmentRepository->deleteOrphaned();
        $this->activitySplitRepository->deleteForActivity($activityId);
        $this->activityLapRepository->deleteForActivity($activityId);
        $this->activityBestEffortRepository->deleteForActivity($activityId);
        $this->activityRepository->delete($activityId);
    }
}
