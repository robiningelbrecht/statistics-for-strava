<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStream;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamTypes;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use Symfony\Component\Console\Output\OutputInterface;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
final readonly class CalculateCombinedStreams implements CalculateActivityMetricsStep
{
    use ProvideTimeFormats;

    public function __construct(
        private ActivityRepository $activityRepository,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private UnitSystem $unitSystem,
        private Mutex $mutex,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated combined activity streams for 0 activities');

        $activityIdsThatNeedCombining = $this->combinedActivityStreamRepository->findActivityIdsThatNeedStreamCombining(
            $this->unitSystem
        );
        $activityWithCombinedStreamCalculatedCount = 0;
        foreach ($activityIdsThatNeedCombining as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $activityType = $activity->getSportType()->getActivityType();

            $streams = $this->activityStreamRepository->findByActivityId($activityId);
            if (!($distanceStream = $streams->filterOnType(StreamType::DISTANCE)) instanceof ActivityStream) {
                continue;
            }
            $combinedStreamTypes = CombinedStreamTypes::fromArray([
                CombinedStreamType::DISTANCE,
            ]);

            $otherStreams = ActivityStreams::empty();
            /** @var CombinedStreamType $combinedStreamType */
            foreach (CombinedStreamTypes::othersFor($activity->getSportType()->getActivityType()) as $combinedStreamType) {
                if (!($stream = $streams->filterOnType($combinedStreamType->getStreamType())) instanceof ActivityStream) {
                    continue;
                }
                if (!$stream->getData()) {
                    continue;
                }

                if (StreamType::ALTITUDE === $stream->getStreamType()) {
                    // Smoothen the altitude stream to remove noise and have a smooth line.
                    $stream = $stream->applySimpleMovingAverage(5);
                }
                if (StreamType::WATTS === $stream->getStreamType()) {
                    // Smoothen the power stream to remove noise and have a smooth line.
                    $stream = $stream->applySimpleMovingAverage(3);
                }
                if (in_array($activity->getSportType()->getActivityType(), [ActivityType::RUN, ActivityType::WALK])
                    && StreamType::VELOCITY === $stream->getStreamType()) {
                    // Smoothen the velocity stream to remove peaks in velocity due to GPS issues.
                    $stream = $stream->applySimpleMovingAverage(15);
                }

                $combinedStreamTypes->add($combinedStreamType);
                $otherStreams->add($stream);
            }

            $combinedData = [];
            $movingIndexes = $streams->filterOnType(StreamType::MOVING)?->getData();
            $velocityData = $otherStreams->filterOnType(StreamType::VELOCITY)?->getData() ?? [];
            $distances = $distanceStream->getData();

            foreach ($distances as $i => $distance) {
                if (null !== $movingIndexes && [] !== $movingIndexes && false === $movingIndexes[$i]) {
                    // Athlete was not moving.
                    continue;
                }

                if ([] !== $velocityData && $velocityData[$i] < 0.5) {
                    // VERY slow velocity data, athlete was probably not moving.
                    // Consider this invalid data.
                    continue;
                }

                $otherPoints = [];
                /** @var ActivityStream $otherStream */
                foreach ($otherStreams as $otherStream) {
                    $otherPoints[] = $otherStream->getData()[$i] ?? 0;
                }

                $combinedData[] = [
                    $distance,
                    ...$otherPoints,
                ];
            }

            // We need to add these after the CombinedStreamTypes::othersFor() otherwise we'll end up with "Undefined array key" errors.
            // This is because CombinedStreamTypes::othersFor() does not return LAT_LNG and TIME as these are not really "combined" streams.
            if (($latLngStream = $streams->filterOnType(StreamType::LAT_LNG)) instanceof ActivityStream) {
                $combinedStreamTypes->add(CombinedStreamType::LAT_LNG);
            }

            $originalCoordinates = $latLngStream?->getData() ?? [];
            $originalTimeData = $streams->filterOnType(StreamType::TIME)?->getData() ?? [];
            $originalMovingData = $streams->filterOnType(StreamType::MOVING)?->getData();

            $cumulativeMovingTime = [];
            if ([] !== $originalTimeData && (null !== $originalMovingData && [] !== $originalMovingData)) {
                $cumulativeMovingTime = [0];
                for ($i = 1, $len = count($originalTimeData); $i < $len; ++$i) {
                    $delta = $originalTimeData[$i] - $originalTimeData[$i - 1];
                    $cumulativeMovingTime[$i] = $cumulativeMovingTime[$i - 1] + ($originalMovingData[$i] ? $delta : 0);
                }

                if (abs(end($cumulativeMovingTime) - $activity->getMovingTimeInSeconds()) > 300) {
                    // For some reason the calculated moving time does not match or reflect the activity moving time.
                    // This means the Strava time stream is fucked up somehow. Discard it.
                    $cumulativeMovingTime = [];
                }
            }

            if ([] !== $cumulativeMovingTime) {
                $combinedStreamTypes->add(CombinedStreamType::TIME);
            }

            $combinedStreamTypesScalar = $combinedStreamTypes->toArray();
            $distanceIndex = array_search(CombinedStreamType::DISTANCE, $combinedStreamTypesScalar, true);
            $altitudeIndex = array_search(CombinedStreamType::ALTITUDE, $combinedStreamTypesScalar, true);
            $paceIndex = array_search(CombinedStreamType::PACE, $combinedStreamTypesScalar, true);
            $velocityIndex = array_search(CombinedStreamType::VELOCITY, $combinedStreamTypesScalar, true);
            $powerIndex = array_search(CombinedStreamType::WATTS, $combinedStreamTypesScalar, true);
            $coordinateIndex = array_search(CombinedStreamType::LAT_LNG, $combinedStreamTypesScalar, true);
            $timeIndex = array_search(CombinedStreamType::TIME, $combinedStreamTypesScalar, true);
            $stepsPerMinuteIndex = array_search(CombinedStreamType::STEPS_PER_MINUTE, $combinedStreamTypesScalar, true);

            // Make sure necessary streams are converted before saving,
            // So we do not need to convert it when reading the data.
            foreach ($combinedData as &$row) {
                $distance = $row[$distanceIndex];

                $indexForOriginalDistance = array_search($distance, $distances);
                if (false !== $coordinateIndex && [] !== $originalCoordinates) {
                    // Find corresponding coordinate for distance.
                    $row[$coordinateIndex] = $originalCoordinates[$indexForOriginalDistance];
                }
                if ([] !== $cumulativeMovingTime) {
                    // Find corresponding time for distance.
                    $movingTimeUntilThisPoint = $cumulativeMovingTime[$indexForOriginalDistance];
                    $row[$timeIndex] = $this->formatDurationForHumans($movingTimeUntilThisPoint);
                }

                $distanceInKm = Meter::from($distance)->toKilometer();
                $row[$distanceIndex] = $distanceInKm->toFloat();

                if (UnitSystem::IMPERIAL === $this->unitSystem) {
                    $row[$distanceIndex] = $distanceInKm->toMiles()->toFloat();
                }

                if (false !== $altitudeIndex && UnitSystem::IMPERIAL === $this->unitSystem) {
                    $row[$altitudeIndex] = Meter::from($row[$altitudeIndex])->toFoot()->toFloat();
                }

                if (false !== $paceIndex) {
                    $secondsPerKilometer = MetersPerSecond::from($row[$paceIndex])->toSecPerKm();
                    if (UnitSystem::IMPERIAL === $this->unitSystem) {
                        $row[$paceIndex] = $secondsPerKilometer->toSecPerMile()->toInt();
                    }
                    if (UnitSystem::METRIC === $this->unitSystem) {
                        $row[$paceIndex] = $secondsPerKilometer->toInt();
                    }
                }

                if (false !== $velocityIndex) {
                    $kmPerHour = MetersPerSecond::from($row[$velocityIndex])->toKmPerHour();
                    if (UnitSystem::IMPERIAL === $this->unitSystem) {
                        $row[$velocityIndex] = $kmPerHour->toMph()->toFloat();
                    }
                    if (UnitSystem::METRIC === $this->unitSystem) {
                        $row[$velocityIndex] = $kmPerHour->toFloat();
                    }
                }

                if (false !== $stepsPerMinuteIndex) {
                    // Convert "Stride per minute" to "Steps per minute"
                    $row[$stepsPerMinuteIndex] *= 2;
                }

                // Apply rounding rules.
                $row[$distanceIndex] = match ($activityType) {
                    ActivityType::RIDE => $row[$distanceIndex] < 1 ? round($row[$distanceIndex], 1) : round($row[$distanceIndex]),
                    default => round($row[$distanceIndex], 1),
                };
                if (false !== $altitudeIndex) {
                    $row[$altitudeIndex] = round($row[$altitudeIndex], 2);
                }
                if (false !== $powerIndex) {
                    $row[$powerIndex] = round($row[$powerIndex]);
                }
                if (false !== $velocityIndex) {
                    $row[$velocityIndex] = round($row[$velocityIndex], 1);
                }
            }

            $this->combinedActivityStreamRepository->add(
                CombinedActivityStream::create(
                    activityId: $activityId,
                    unitSystem: $this->unitSystem,
                    streamTypes: $combinedStreamTypes,
                    data: $combinedData,
                )
            );
            ++$activityWithCombinedStreamCalculatedCount;
            $progressIndicator->updateMessage(sprintf(
                '=> Calculated combined activity streams for %d activities',
                $activityWithCombinedStreamCalculatedCount
            ));
            $this->mutex->heartbeat();
        }

        $progressIndicator->finish(sprintf(
            '=> Calculated combined activity streams for %d activities',
            $activityWithCombinedStreamCalculatedCount
        ));
    }
}
