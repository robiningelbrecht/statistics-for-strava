<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Math;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\Gap\GapCalculator;
use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStream;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamTypes;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use Symfony\Component\Console\Output\OutputInterface;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
final readonly class CalculateCombinedStreams implements CalculateActivityMetricsStep
{
    use ProvideTimeFormats;

    private const GAP_CHART_ELEVATION_SMOOTHING_WINDOW_SIZE = 25;
    private const GAP_CHART_GRADE_DISTANCE_WINDOW_IN_METERS = 100.0;
    private const GAP_CHART_SMOOTHING_WINDOW_SIZE = 15;

    public function __construct(
        private ActivityRepository $activityRepository,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private UnitSystem $unitSystem,
        private Mutex $mutex,
        private ?GapCalculator $gapCalculator = null,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated combined activity streams for 0 activities');

        $activityIdsThatNeedCombining = $this->combinedActivityStreamRepository->findActivityIdsThatNeedStreamCombining(
            $this->unitSystem
        );
        $gapCalculator = $this->gapCalculator ?? GapCalculator::create();
        $activityWithCombinedStreamCalculatedCount = 0;
        foreach ($activityIdsThatNeedCombining as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $activityType = $activity->getSportType()->getActivityType();

            $streams = $this->activityStreamRepository->findByActivityId($activityId);
            if (!($timeStream = $streams->filterOnType(StreamType::TIME)) instanceof ActivityStream) {
                continue; // @codeCoverageIgnore
            }
            $timeData = $timeStream->getData();
            $movingData = $streams->filterOnType(StreamType::MOVING)?->getData();

            $combinedStreamTypes = CombinedStreamTypes::fromArray([
                CombinedStreamType::TIME,
            ]);

            $distanceData = [];
            if (($distanceStream = $streams->filterOnType(StreamType::DISTANCE)) instanceof ActivityStream) {
                if ($distanceStream->hasValidData() && ($distanceData = $distanceStream->getData())) {
                    $combinedStreamTypes->add(CombinedStreamType::DISTANCE);
                }
            }
            $latLngData = [];
            if (($latLngStream = $streams->filterOnType(StreamType::LAT_LNG)) instanceof ActivityStream) {
                if ($latLngStream->hasValidData() && ($latLngData = $latLngStream->getData())) {
                    $combinedStreamTypes->add(CombinedStreamType::LAT_LNG);
                }
            }
            $gradeData = [];
            if (($gradeStream = $streams->filterOnType(StreamType::GRADE)) instanceof ActivityStream) {
                if ($gradeStream->hasValidData() && ($gradeData = $gradeStream->getData())) {
                    $combinedStreamTypes->add(CombinedStreamType::GRADE);
                }
            }

            /** @var array<int, array{0: CombinedStreamType, 1: ActivityStream}> $otherStreams */
            $otherStreams = [];
            foreach (CombinedStreamTypes::othersFor($activity->getSportType()->getActivityType()) as $combinedStreamType) {
                if (CombinedStreamType::PACE === $combinedStreamType && $gapCalculator->supports($activity->getSportType())) {
                    $gapChartData = $this->buildGapChartData(
                        gapCalculator: $gapCalculator,
                        timeData: $timeData,
                        movingData: $movingData,
                        altitudeData: $streams->filterOnType(StreamType::ALTITUDE)?->getData() ?? [],
                        latLngData: $latLngData,
                    );
                    if ([] !== $gapChartData) {
                        $combinedStreamTypes->add(CombinedStreamType::GAP);
                        $otherStreams[] = [CombinedStreamType::GAP, $gapChartData];
                    }
                }

                if (!($stream = $streams->filterOnType($combinedStreamType->getStreamType())) instanceof ActivityStream) {
                    continue;
                }
                if (!$stream->hasValidData()) {
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
            $cumulativeMovingTime = 0;
            $hasMovingData = null !== $movingData && [] !== $movingData;
            $hasDistanceData = $combinedStreamTypes->has(CombinedStreamType::DISTANCE);
            $hasLatLngData = $combinedStreamTypes->has(CombinedStreamType::LAT_LNG);
            $hasGradeData = $combinedStreamTypes->has(CombinedStreamType::GRADE);

            $maxYAxisValue = PHP_INT_MIN;
            $maxTimeDataIndex = count($timeData) - 1;
            foreach ($timeData as $i => $time) {
                if ($hasMovingData && true === $movingData[$i] && $i < $maxTimeDataIndex) {
                    $delta = $timeData[$i + 1] - $time;
                    // Ignore session gaps (e.g. activity recorded in multiple sessions).
                    if (true === $movingData[$i + 1] || $delta <= 60) {
                        $cumulativeMovingTime += $delta;
                    }
                }

                if ($hasMovingData && false === $movingData[$i]) {
                    // Athlete was not moving.
                    continue;
                }

                $combinedPoint = [
                    $this->formatDurationAsClock($cumulativeMovingTime),
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

                if ($hasGradeData) {
                    $combinedPoint[] = $gradeData[$i];
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

    /**
     * @param list<int> $timeData
     * @param list<bool>|null $movingData
     * @param list<float|int> $altitudeData
     * @param list<array{0: float, 1: float}|null> $latLngData
     *
     * @return list<int>
     */
    private function buildGapChartData(
        GapCalculator $gapCalculator,
        array $timeData,
        ?array $movingData,
        array $altitudeData,
        array $latLngData,
    ): array {
        if ([] === $timeData || [] === $altitudeData || [] === $latLngData) {
            return [];
        }

        $maxIndex = min(\count($timeData), \count($altitudeData), \count($latLngData));
        $trackPoints = [];
        $rawIndexByTrackPointIndex = [];

        for ($i = 0; $i < $maxIndex; ++$i) {
            if (null !== $movingData && [] !== $movingData && false === ($movingData[$i] ?? false)) {
                continue;
            }

            $coordinate = $latLngData[$i] ?? null;
            if (!is_array($coordinate) || 2 !== \count($coordinate)) {
                continue;
            }

            $trackPoints[] = [
                'lat' => (float) $coordinate[0],
                'lon' => (float) $coordinate[1],
                'ele' => (float) $altitudeData[$i],
                'timestamp' => (int) $timeData[$i],
            ];
            $rawIndexByTrackPointIndex[] = $i;
        }

        if (\count($trackPoints) < 2) {
            return [];
        }

        $chartGapCalculator = GapCalculator::create(
            smoothingWindowSize: self::GAP_CHART_ELEVATION_SMOOTHING_WINDOW_SIZE,
            gradeDistanceWindowInMeters: self::GAP_CHART_GRADE_DISTANCE_WINDOW_IN_METERS,
        );

        $pointGapPaces = $chartGapCalculator->calculatePointGapPaces($trackPoints);
        if ([] === $pointGapPaces) {
            return [];
        }

        $gapByRawIndex = [];
        foreach ($rawIndexByTrackPointIndex as $trackPointIndex => $rawIndex) {
            $gapPace = $pointGapPaces[$trackPointIndex] ?? null;
            if (null === $gapPace) {
                continue;
            }

            $gapByRawIndex[$rawIndex] = (int) round($gapPace);
        }

        $chartData = [];
        $currentGapValue = null;
        foreach ($timeData as $i => $_time) {
            if (null !== $movingData && [] !== $movingData && false === ($movingData[$i] ?? false)) {
                continue;
            }

            if (array_key_exists($i, $gapByRawIndex)) {
                $currentGapValue = $gapByRawIndex[$i];
            }

            if (null === $currentGapValue) {
                continue;
            }

            $chartData[$i] = $currentGapValue;
        }

        if ([] === $chartData) {
            return [];
        }

        $smoothedChartData = Math::movingAverage(array_values($chartData), self::GAP_CHART_SMOOTHING_WINDOW_SIZE);

        return array_combine(array_keys($chartData), array_map(
            static fn (int|float $value): int => (int) round($value),
            $smoothedChartData,
        )) ?: [];
    }
}
