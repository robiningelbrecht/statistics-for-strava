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
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Logging\Monolog;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Psr\Log\LoggerInterface;

final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private Strava $strava,
        private LoggerInterface $logger,
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

                // Fetch polyline data from Strava API if this is a new segment
                $polyline = null;
                if (!isset($segmentsAddedInCurrentRun[(string) $segmentId])) {
                    try {
                        $existingSegment = $this->segmentRepository->find($segmentId);
                        $segment = $existingSegment;
                    } catch (EntityNotFound) {
                        // This is a new segment, fetch detailed data from Strava
                        try {
                            $segmentDetails = $this->strava->getSegment($segmentId);
                            $polyline = $segmentDetails['map']['polyline'] ?? null;
                            
                            if ($polyline) {
                                $this->logger->info(new Monolog(
                                    'Successfully fetched segment polyline from Strava API',
                                    'segment_id: '.$segmentId->toUnprefixedString(),
                                    'polyline_length: '.strlen($polyline)
                                ));
                            } else {
                                $this->logger->warning(new Monolog(
                                    'Segment fetched from Strava API but no polyline data available',
                                    'segment_id: '.$segmentId->toUnprefixedString(),
                                    'segment_name: '.$activitySegment['name']
                                ));
                            }
                        } catch (\Exception $e) {
                            // If we can't fetch segment details, continue without polyline
                            $polyline = null;
                            $this->logger->warning(new Monolog(
                                'Failed to fetch segment details from Strava API, continuing without polyline',
                                'segment_id: '.$segmentId->toUnprefixedString(),
                                'segment_name: '.$activitySegment['name'],
                                'error: '.$e->getMessage()
                            ));
                        }
                        
                        $segment = Segment::create(
                            segmentId: $segmentId,
                            name: Name::fromString($activitySegment['name']),
                            sportType: $activity->getSportType(),
                            distance: Meter::from($activitySegment['distance'])->toKilometer(),
                            maxGradient: $activitySegment['maximum_grade'],
                            isFavourite: isset($activitySegment['starred']) && $activitySegment['starred'],
                            climbCategory: $activitySegment['climb_category'] ?? null,
                            deviceName: $activity->getDeviceName(),
                            polyline: $polyline,
                        );
                        
                        $this->segmentRepository->add($segment);
                        $segmentsAddedInCurrentRun[(string) $segmentId] = $segmentId;
                        ++$countSegmentsAdded;
                    }
                } else {
                    // Segment already processed in this run, create a temporary object for effort processing
                    $segment = Segment::create(
                        segmentId: $segmentId,
                        name: Name::fromString($activitySegment['name']),
                        sportType: $activity->getSportType(),
                        distance: Meter::from($activitySegment['distance'])->toKilometer(),
                        maxGradient: $activitySegment['maximum_grade'],
                        isFavourite: isset($activitySegment['starred']) && $activitySegment['starred'],
                        climbCategory: $activitySegment['climb_category'] ?? null,
                        deviceName: $activity->getDeviceName(),
                    );
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
