<?php

declare(strict_types=1);

namespace App\Application\Import\ImportSegments;

use App\Application\Countries;
use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Segment\Segment;
use App\Domain\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentId;
use App\Domain\Segment\SegmentRepository;
use App\Domain\Strava\RateLimit\StravaRateLimitHasBeenReached;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Geography\EncodedPolyline;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

#[WithMutex(lockName: 'importDataOrBuildApp')]
final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private OptInToSegmentDetailsImport $optInToSegmentDetailsImport,
        private Strava $strava,
        private Countries $countries,
        private Mutex $mutex,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportSegments);
        $command->getOutput()->writeln('Importing segments and efforts...');

        $this->strava->setConsoleOutput($command->getOutput());

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

                // Do not process segments that have been imported in the current run.
                if (!isset($segmentsProcessedInCurrentRun[(string) $segmentId])) {
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
                            countryCode: $countryCode,
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
                        segmentId: $segmentId,
                        activityId: $activity->getId(),
                        startDateTime: SerializableDateTime::createFromFormat(
                            Activity::DATE_TIME_FORMAT,
                            $activitySegmentEffort['start_date_local']
                        ),
                        name: $activitySegmentEffort['name'],
                        elapsedTimeInSeconds: (float) $activitySegmentEffort['elapsed_time'],
                        distance: Meter::from($activitySegment['distance'])->toKilometer(),
                        averageWatts: isset($activitySegmentEffort['average_watts']) ? (float) $activitySegmentEffort['average_watts'] : null,
                        averageHeartRate: isset($activitySegmentEffort['average_heartrate']) ? (int) $activitySegmentEffort['average_heartrate'] : null,
                        maxHeartRate: isset($activitySegmentEffort['max_heartrate']) ? (int) $activitySegmentEffort['max_heartrate'] : null,
                    ));
                    ++$countSegmentEffortsAdded;
                }
            }
        }
        $command->getOutput()->writeln(sprintf('  => Added %d new segments and %d new segment efforts', $countSegmentsAdded, $countSegmentEffortsAdded));

        if (!$this->optInToSegmentDetailsImport->hasOptedIn()) {
            return;
        }

        $segmentIdsMissingDetails = $this->segmentRepository->findSegmentsIdsMissingDetails();
        $numberOfSegmentIdsMissingDetails = count($segmentIdsMissingDetails);
        $delta = 1;
        foreach ($segmentIdsMissingDetails as $segmentId) {
            $segment = $this->segmentRepository->find($segmentId);
            try {
                $stravaSegment = $this->strava->getSegment($segmentId);
                $this->mutex->heartbeat();

                $segment->updatePolyline(
                    EncodedPolyline::fromOptionalString(
                        $stravaSegment['map']['polyline'] ?? null
                    )
                );
                $segment->flagDetailsAsImported();
                $this->segmentRepository->update($segment);

                $command->getOutput()->writeln(
                    sprintf(
                        '  => [%d/%d] Imported segment details: "%s"',
                        $delta,
                        $numberOfSegmentIdsMissingDetails,
                        $segment->getName()
                    )
                );

                ++$delta;
            } catch (StravaRateLimitHasBeenReached $exception) {
                $command->getOutput()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

                return;
            } catch (ClientException|RequestException $exception) {
                if (404 === $exception->getResponse()?->getStatusCode()) {
                    // Segment does not exist anymore. Mark as imported.
                    $segment->flagDetailsAsImported();
                    $this->segmentRepository->update($segment);
                }

                $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));
                continue;
            }
        }
    }
}
