<?php

namespace App\Application\Import\DeleteActivitiesMarkedForDeletion;

use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivitySummaryRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\Cache\CacheTagDependency\CacheTagDependencyRepository;
use App\Infrastructure\Cache\InvalidatedCacheTag\InvalidatedCacheTagRepository;
use App\Infrastructure\Cache\Tag;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivitiesMarkedForDeletionCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityIdRepository $activityIdRepository,
        private ActivitySummaryRepository $activitySummaryRepository,
        private ActivityRepository $activityRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamMetricRepository $activityStreamMetricRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private SegmentRepository $segmentRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityLapRepository $activityLapRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private InvalidatedCacheTagRepository $invalidatedCacheTagRepository,
        private CacheTagDependencyRepository $cacheTagDependencyRepository,
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

            $affectedSegmentIds = $this->segmentEffortRepository->findUniqueSegmentIdsForActivity($activityId);

            $this->activityStreamRepository->deleteForActivity($activityId);
            $this->activityStreamMetricRepository->deleteForActivity($activityId);
            $this->segmentEffortRepository->deleteForActivity($activityId);
            $this->segmentRepository->deleteOrphaned();
            $this->activitySplitRepository->deleteForActivity($activityId);
            $this->activityLapRepository->deleteForActivity($activityId);
            $this->activityBestEffortRepository->deleteForActivity($activityId);
            $this->activityRepository->delete($activityId);

            $this->invalidatedCacheTagRepository->invalidate(Tag::activity((string) $activityId));
            $this->cacheTagDependencyRepository->clearForEntity('activity', (string) $activityId);

            foreach ($affectedSegmentIds as $segmentId) {
                $this->invalidatedCacheTagRepository->invalidate(Tag::segment((string) $segmentId));
            }

            $command->getOutput()->writeln(sprintf(
                '  => Activity "%s - %s" deleted',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'))
            );
        }
    }
}
