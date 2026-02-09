<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\ActivityStreamRepository;
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
            if (!($timeStream = $streams->filterOnType(StreamType::TIME)) instanceof ActivityStream) {
                continue; // @codeCoverageIgnore
            }
            $combinedStreamTypes = CombinedStreamTypes::fromArray([
                CombinedStreamType::TIME,
            ]);

            $distanceData = [];
            if (($distanceStream = $streams->filterOnType(StreamType::DISTANCE)) instanceof ActivityStream) {
                if ($distanceData = $distanceStream->getData()) {
                    $combinedStreamTypes->add(CombinedStreamType::DISTANCE);
                }
            }
            $latLngData = [];
            if (($latLngStream = $streams->filterOnType(StreamType::LAT_LNG)) instanceof ActivityStream) {
                if ($latLngData = $latLngStream->getData()) {
                    $combinedStreamTypes->add(CombinedStreamType::LAT_LNG);
                }
            }

            /** @var array<int, array{0: CombinedStreamType, 1: ActivityStream}> $otherStreams */
            $otherStreams = [];
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
                $otherStreams[] = [$combinedStreamType, $stream->getData()];
            }

            $combinedData = [];
            $timeData = $timeStream->getData();
            $movingData = $streams->filterOnType(StreamType::MOVING)?->getData();

            $cumulativeMovingTime = 0;
            $hasMovingData = null !== $movingData && [] !== $movingData;
            $hasDistanceData = $combinedStreamTypes->has(CombinedStreamType::DISTANCE);
            $hasLatLngData = $combinedStreamTypes->has(CombinedStreamType::LAT_LNG);

            $maxYAxisValue = PHP_INT_MIN;
            foreach ($timeData as $i => $time) {
                if ($i > 0 && $hasMovingData && true === $movingData[$i - 1]) {
                    // Update moving time based on the previous interval.
                    $cumulativeMovingTime += $time - $timeData[$i - 1];
                }

                if ($hasMovingData && false === $movingData[$i]) {
                    // Athlete was not moving.
                    continue;
                }

                $combinedPoint = [
                    $this->formatDurationForHumans($cumulativeMovingTime),
                ];

                if ($hasDistanceData) {
                    $distance = Meter::from($distanceData[$i])->toKilometer()->toUnitSystem($this->unitSystem)->toFloat();
                    $combinedPoint[] = match ($activityType) {
                        ActivityType::RIDE => $distance < 1 ? round($distance, 1) : round($distance),
                        default => round($distance, 1),
                    };
                }

                if ($hasLatLngData) {
                    $combinedPoint[] = $latLngData[$i];
                }

                foreach ($otherStreams as $otherStream) {
                    /** @var CombinedStreamType $combinedStreamType */
                    /** @var array<int|float> $streamData */
                    [$combinedStreamType, $streamData] = $otherStream;
                    $value = $streamData[$i] ?? 0;

                    if (0 !== $value && 0.0 !== $value) {
                        $value = match ($combinedStreamType) {
                            CombinedStreamType::ALTITUDE => round(Meter::from($value)->toUnitSystem($this->unitSystem)->toFloat(), 2),
                            CombinedStreamType::VELOCITY => round(MetersPerSecond::from($value)->toKmPerHour()->toUnitSystem($this->unitSystem)->toFloat(), 1),
                            CombinedStreamType::PACE => MetersPerSecond::from($value)->toSecPerKm()->toUnitSystem($this->unitSystem)->toInt(),
                            CombinedStreamType::STEPS_PER_MINUTE => $value * 2,
                            CombinedStreamType::WATTS => round($value),
                            default => $value,
                        };
                    }

                    $maxYAxisValue = max($maxYAxisValue, $value);
                    $combinedPoint[] = $value;
                }

                $combinedData[] = $combinedPoint;
            }

            $this->combinedActivityStreamRepository->add(
                CombinedActivityStream::create(
                    activityId: $activityId,
                    unitSystem: $this->unitSystem,
                    streamTypes: $combinedStreamTypes,
                    data: $combinedData,
                    maxYAxisValue: (int) $maxYAxisValue,
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
