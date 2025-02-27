<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportSegments);
        $command->getOutput()->writeln('Importing segments and efforts...');

        $segmentsAddedInCurrentRun = [];

        $countSegmentsAdded = 0;
        $countSegmentEffortsAdded = 0;

        // @TODO: Only check activities that have no segment efforts imported yet?
        foreach ($this->activityRepository->findActivityIds() as $activityId) {
            $activityWithRawData = $this->activityWithRawDataRepository->find($activityId);
            if (!$segmentEfforts = $activityWithRawData->getSegmentEfforts()) {
                continue;
            }

            $activity = $activityWithRawData->getActivity();
            foreach ($segmentEfforts as $activitySegmentEffort) {
                $activitySegment = $activitySegmentEffort['segment'];
                $segmentId = SegmentId::fromUnprefixed((string) $activitySegment['id']);

                $segment = Segment::create(
                    segmentId: $segmentId,
                    name: Name::fromString($activitySegment['name']),
                    sportType: $activity->getSportType(),
                    distance: Kilometer::from($activitySegment['distance'] / 1000),
                    maxGradient: $activitySegment['maximum_grade'],
                    isFavourite: isset($activitySegment['starred']) && $activitySegment['starred'],
                    deviceName: $activity->getDeviceName(),
                );

                // Do not import segments that have been imported in the current run.
                if (!isset($segmentsAddedInCurrentRun[(string) $segmentId])) {
                    // Check if the segment is imported in a previous run.
                    try {
                        $segment = $this->segmentRepository->find($segment->getId());
                    } catch (EntityNotFound) {
                        $this->segmentRepository->add($segment);
                        $segmentsAddedInCurrentRun[(string) $segmentId] = $segmentId;
                        ++$countSegmentsAdded;
                    }
                }

                $segmentEffortId = SegmentEffortId::fromUnprefixed((string) $activitySegmentEffort['id']);
                try {
                    $this->segmentEffortRepository->find($segmentEffortId);
                } catch (EntityNotFound) {
                    $this->segmentEffortRepository->add(SegmentEffort::create(
                        segmentEffortId: $segmentEffortId,
                        segmentId: $segment->getId(),
                        activityId: $activity->getId(),
                        startDateTime: SerializableDateTime::createFromFormat(
                            Activity::DATE_TIME_FORMAT,
                            $activitySegmentEffort['start_date_local']
                        ),
                        name: $activitySegmentEffort['name'],
                        elapsedTimeInSeconds: (float) $activitySegmentEffort['elapsed_time'],
                        distance: Kilometer::from($activitySegment['distance'] / 1000),
                        averageWatts: isset($activitySegmentEffort['average_watts']) ? (float) $activitySegmentEffort['average_watts'] : null,
                    ));
                    ++$countSegmentEffortsAdded;
                }
            }
        }

        $command->getOutput()->writeln(sprintf('  => Added %d new segments and %d new segment efforts', $countSegmentsAdded, $countSegmentEffortsAdded));
    }
}
