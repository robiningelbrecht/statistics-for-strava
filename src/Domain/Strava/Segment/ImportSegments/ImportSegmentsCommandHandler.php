<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ImportSegments;

use App\Domain\App\Countries;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private Countries $countries,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportSegments);
        $command->getOutput()->writeln('Importing segments and efforts...');

        $segmentsProcessedInCurrentRun = [];

        $countSegmentsAdded = 0;
        $countSegmentEffortsAdded = 0;

        foreach ($this->activityRepository->findActivityIds() as $activityId) {
            $activityWithRawData = $this->activityWithRawDataRepository->find($activityId);
            if (!$segmentEfforts = $activityWithRawData->getSegmentEfforts()) {
                continue;
            }

            $activity = $activityWithRawData->getActivity();
            foreach ($segmentEfforts as $activitySegmentEffort) {
                $activitySegment = $activitySegmentEffort['segment'];
                $segmentId = SegmentId::fromUnprefixed((string) $activitySegment['id']);

                $countryCode = null;
                if ($activity->getSportType()->supportsReverseGeocoding() && !empty($activitySegment['country'])) {
                    $countryCode = $this->countries->findCountryCodeByCountryName($activitySegment['country']);
                }

                $isFavourite = isset($activitySegment['starred']) && $activitySegment['starred'];

                // Do not import segments that have been imported in the current run.
                if (!isset($segmentsProcessedInCurrentRun[(string) $segmentId])) {
                    // Check if the segment is imported in a previous run.
                    try {
                        $segment = $this->segmentRepository->find($segmentId);
                        if ($isFavourite !== $segment->isFavourite()) {
                            $segment->updateIsFavourite($isFavourite);
                            $this->segmentRepository->update($segment);
                        }
                    } catch (EntityNotFound) {
                        $segment = Segment::create(
                            segmentId: $segmentId,
                            name: Name::fromString($activitySegment['name']),
                            sportType: $activity->getSportType(),
                            distance: Meter::from($activitySegment['distance'])->toKilometer(),
                            maxGradient: $activitySegment['maximum_grade'],
                            isFavourite: $isFavourite,
                            climbCategory: $activitySegment['climb_category'] ?? null,
                            deviceName: $activity->getDeviceName(),
                            countryCode: $countryCode
                        );
                        $this->segmentRepository->add($segment);
                        ++$countSegmentsAdded;
                    }
                    $segmentsProcessedInCurrentRun[(string) $segmentId] = $segmentId;
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
                        distance: Meter::from($activitySegment['distance'])->toKilometer(),
                        averageWatts: isset($activitySegmentEffort['average_watts']) ? (float) $activitySegmentEffort['average_watts'] : null,
                    ));
                    ++$countSegmentEffortsAdded;
                }
            }
        }

        $command->getOutput()->writeln(sprintf('  => Added %d new segments and %d new segment efforts', $countSegmentsAdded, $countSegmentEffortsAdded));
    }
}
