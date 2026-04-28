<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\Gap\GapCalculator;
use App\Domain\Activity\Gap\GapSegment;
use App\Domain\Activity\Split\ActivitySplit;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\Split\ActivitySplits;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Console\ProgressIndicator;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateGap implements CalculateActivityMetricsStep
{
    public function __construct(
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('=> Calculated GAP for 0 activities');

        $countActivitiesProcessed = $this->recalculateForActivityIds(
            output: $output,
            activityIdsToProcess: $this->activitySplitRepository->findActivityIdsWithoutGap(),
            progressIndicator: $progressIndicator,
        );

        $progressIndicator->finish(sprintf('=> Calculated GAP for %d activities', $countActivitiesProcessed));
    }

    public function recalculateForActivityIds(
        OutputInterface $output,
        ActivityIds $activityIdsToProcess,
        ?ProgressIndicator $progressIndicator = null,
    ): int {
        $countActivitiesProcessed = 0;
        foreach ($activityIdsToProcess as $activityId) {
            $trackPoints = $this->buildTrackPoints($activityId);
            if ([] === $trackPoints) {
                continue;
            }

            $gapCalculator = GapCalculator::create();

            /** @var list<GapSegment> $segments */
            $segments = iterator_to_array($gapCalculator->calculateSegments($trackPoints), false);
            if ([] === $segments) {
                continue;
            }

            $gapEnrichedSplits = ActivitySplits::empty()
                ->addMultiple($this->mapSegmentsToSplits($segments, $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)))
                ->addMultiple($this->mapSegmentsToSplits($segments, $this->activitySplitRepository->findBy($activityId, UnitSystem::IMPERIAL)));

            foreach ($gapEnrichedSplits as $split) {
                $this->activitySplitRepository->update($split);
            }

            ++$countActivitiesProcessed;
            $progressIndicator?->updateMessage(sprintf('=> Calculated GAP for %d activities', $countActivitiesProcessed));
        }

        return $countActivitiesProcessed;
    }

    /**
     * @return list<array{lat: float, lon: float, ele: float, timestamp: int, grade?: float|null}>
     */
    private function buildTrackPoints(ActivityId $activityId): array
    {
        $streams = $this->activityStreamRepository->findByActivityId($activityId);

        $latLng = $streams->filterOnType(StreamType::LAT_LNG)?->getData() ?? [];
        $altitude = $streams->filterOnType(StreamType::ALTITUDE)?->getData() ?? [];
        $time = $streams->filterOnType(StreamType::TIME)?->getData() ?? [];
        $grade = $streams->filterOnType(StreamType::GRADE)?->getData() ?? [];
        if (in_array([], [$latLng, $altitude, $time], true)) {
            return [];
        }

        $moving = $streams->filterOnType(StreamType::MOVING)?->getData() ?? [];
        $hasMovingStream = [] !== $moving;
        $maxIndex = min(count($latLng), count($altitude), count($time));

        $trackPoints = [];
        for ($i = 0; $i < $maxIndex; ++$i) {
            if ($hasMovingStream && false === ($moving[$i] ?? false)) {
                continue;
            }

            $coordinate = $latLng[$i] ?? null;
            if (!is_array($coordinate)) {
                continue;
            }
            if (2 !== count($coordinate)) {
                continue;
            }

            $trackPoints[] = [
                'lat' => (float) $coordinate[0],
                'lon' => (float) $coordinate[1],
                'ele' => (float) $altitude[$i],
                'timestamp' => (int) $time[$i],
                'grade' => isset($grade[$i]) && is_numeric($grade[$i]) ? (float) $grade[$i] : null,
            ];
        }

        return $trackPoints;
    }

    /**
     * @param list<GapSegment> $gapSegments
     *
     * @return ActivitySplit[]
     */
    private function mapSegmentsToSplits(array $gapSegments, ActivitySplits $splits): array
    {
        if ($splits->isEmpty()) {
            return [];
        }

        $splitItems = array_values($splits->toArray());
        $currentSplitIndex = 0;
        $distanceInCurrentSplit = 0.0;
        $durationInCurrentSplit = 0.0;
        $adjustedDistanceInCurrentSplit = 0.0;
        $weightedObservedGradeInCurrentSplit = 0.0;

        foreach ($gapSegments as $segment) {
            $remainingDistance = $segment->getDistanceInMeters();
            $remainingDuration = (float) $segment->getDurationInSeconds();
            $remainingAdjustedDistance = $segment->getDistanceInMeters() * $segment->getGapMultiplier();
            $remainingWeightedObservedGrade = abs($segment->getGrade()) * $segment->getDistanceInMeters();

            while ($remainingDistance > 0.0 && isset($splitItems[$currentSplitIndex])) {
                $split = $splitItems[$currentSplitIndex];
                $targetDistance = $split->getDistance()->toFloat();
                $remainingInSplit = $targetDistance - $distanceInCurrentSplit;

                if ($remainingInSplit <= 0.00001) {
                    $splitItems[$currentSplitIndex] = $this->finalizeSplitGap($split, $targetDistance, $distanceInCurrentSplit, $durationInCurrentSplit, $adjustedDistanceInCurrentSplit, $weightedObservedGradeInCurrentSplit);
                    ++$currentSplitIndex;
                    $distanceInCurrentSplit = 0.0;
                    $durationInCurrentSplit = 0.0;
                    $adjustedDistanceInCurrentSplit = 0.0;
                    $weightedObservedGradeInCurrentSplit = 0.0;
                    continue;
                }

                $distancePortion = min($remainingDistance, $remainingInSplit);
                $ratio = $distancePortion / $remainingDistance;
                $durationPortion = $remainingDuration * $ratio;
                $adjustedDistancePortion = $remainingAdjustedDistance * $ratio;
                $weightedObservedGradePortion = $remainingWeightedObservedGrade * $ratio;

                $distanceInCurrentSplit += $distancePortion;
                $durationInCurrentSplit += $durationPortion;
                $adjustedDistanceInCurrentSplit += $adjustedDistancePortion;
                $weightedObservedGradeInCurrentSplit += $weightedObservedGradePortion;

                $remainingDistance -= $distancePortion;
                $remainingDuration -= $durationPortion;
                $remainingAdjustedDistance -= $adjustedDistancePortion;
                $remainingWeightedObservedGrade -= $weightedObservedGradePortion;

                if ($distanceInCurrentSplit >= $targetDistance - 0.00001) {
                    $splitItems[$currentSplitIndex] = $this->finalizeSplitGap($split, $targetDistance, $distanceInCurrentSplit, $durationInCurrentSplit, $adjustedDistanceInCurrentSplit, $weightedObservedGradeInCurrentSplit);
                    ++$currentSplitIndex;
                    $distanceInCurrentSplit = 0.0;
                    $durationInCurrentSplit = 0.0;
                    $adjustedDistanceInCurrentSplit = 0.0;
                    $weightedObservedGradeInCurrentSplit = 0.0;
                }
            }

            if (!isset($splitItems[$currentSplitIndex])) {
                break;
            }
        }

        if (isset($splitItems[$currentSplitIndex]) && $adjustedDistanceInCurrentSplit > 0.0) {
            $splitItems[$currentSplitIndex] = $splitItems[$currentSplitIndex]->withGapPace(
                $this->resolveGapPace(
                    $splitItems[$currentSplitIndex],
                    ($durationInCurrentSplit / $adjustedDistanceInCurrentSplit) * 1000.0,
                    $distanceInCurrentSplit,
                    $weightedObservedGradeInCurrentSplit,
                )
            );
        }

        return $splitItems;
    }

    private function finalizeSplitGap(
        ActivitySplit $split,
        float $targetDistanceInMeters,
        float $distanceInCurrentSplit,
        float $durationInCurrentSplit,
        float $adjustedDistanceInCurrentSplit,
        float $weightedObservedGradeInCurrentSplit,
    ): ActivitySplit {
        if ($distanceInCurrentSplit < $targetDistanceInMeters - 0.00001 || $adjustedDistanceInCurrentSplit <= 0.0) {
            return $split;
        }

        return $split->withGapPace($this->resolveGapPace(
            $split,
            ($durationInCurrentSplit / $adjustedDistanceInCurrentSplit) * 1000.0,
            $distanceInCurrentSplit,
            $weightedObservedGradeInCurrentSplit,
        ));
    }

    private function resolveGapPace(
        ActivitySplit $split,
        float $calculatedGapPaceInSecondsPerKm,
        float $distanceInCurrentSplit,
        float $weightedObservedGradeInCurrentSplit,
    ): SecPerKm {
        $actualPaceInSecondsPerKm = $split->getPaceInSecPerKm()->toFloat();
        $distanceInMeters = max(1.0, $distanceInCurrentSplit);
        $signedNetElevationDifferenceInMeters = $split->getElevationDifference()->toFloat();
        $netElevationDifferenceInMeters = abs($signedNetElevationDifferenceInMeters);
        $netGrade = $netElevationDifferenceInMeters / $distanceInMeters;
        $observedGrade = $weightedObservedGradeInCurrentSplit / $distanceInMeters;

        // Flat or near-flat splits should not drift far from the actual pace,
        // even if point-level grade data or grade_smooth is noisy.
        if ($signedNetElevationDifferenceInMeters < 0.0 && ($netElevationDifferenceInMeters <= 2.0 || $netGrade < 0.002)) {
            return SecPerKm::from(min(
                $actualPaceInSecondsPerKm * 1.01,
                max($actualPaceInSecondsPerKm, $calculatedGapPaceInSecondsPerKm),
            ));
        }

        if ($signedNetElevationDifferenceInMeters < 0.0 && ($netElevationDifferenceInMeters <= 5.0 || $netGrade < 0.005)) {
            return SecPerKm::from(min(
                $actualPaceInSecondsPerKm * 1.01,
                max($actualPaceInSecondsPerKm * 0.99, $calculatedGapPaceInSecondsPerKm),
            ));
        }

        if ($signedNetElevationDifferenceInMeters > 0.0) {
            $uphillBenefitRatio = match (true) {
                $netGrade < 0.01 => 0.02,
                $netGrade < 0.02 => 0.04,
                $netGrade < 0.03 => 0.06,
                default => 0.10,
            };

            return SecPerKm::from(min(
                $actualPaceInSecondsPerKm,
                max($actualPaceInSecondsPerKm * (1.0 - $uphillBenefitRatio), $calculatedGapPaceInSecondsPerKm),
            ));
        }

        // Beyond tiny descents, downhill should stop looking like "free speed"
        // in the split table. Use a mild penalty for moderate downhill and a
        // stronger one for steep downhill.
        if ($signedNetElevationDifferenceInMeters < 0.0 && $netGrade >= 0.005) {
            $downhillPenaltyRatio = $netGrade >= 0.03
                ? min(0.10, 0.035 + (($netGrade - 0.03) * 2.0))
                : min(0.05, 0.008 + (($netGrade - 0.005) * 1.6));

            return SecPerKm::from($actualPaceInSecondsPerKm * (1.0 + $downhillPenaltyRatio));
        }

        if ($netElevationDifferenceInMeters <= 5.0 || $netGrade < 0.005) {
            $allowedDeviationRatio = 0.02;
        } elseif ($netGrade < 0.01 || $netElevationDifferenceInMeters <= 10.0) {
            $allowedDeviationRatio = 0.03;
        } else {
            $allowedDeviationRatio = null;
        }

        if (null !== $allowedDeviationRatio) {
            return SecPerKm::from(min(
                $actualPaceInSecondsPerKm * (1.0 + $allowedDeviationRatio),
                max($actualPaceInSecondsPerKm * (1.0 - $allowedDeviationRatio), $calculatedGapPaceInSecondsPerKm),
            ));
        }

        // GPS altitude noise can create unrealistic short-window grades. Keep
        // the split GAP within a plausible band around the actual split pace.
        $allowedDeviationRatio = min(0.35, 0.08 + max($netGrade, $observedGrade) * 4.0);
        $minimumGapPace = $actualPaceInSecondsPerKm * (1.0 - $allowedDeviationRatio);
        $maximumGapPace = $actualPaceInSecondsPerKm * (1.0 + $allowedDeviationRatio);

        return SecPerKm::from(min(
            $maximumGapPace,
            max($minimumGapPace, $calculatedGapPaceInSecondsPerKm),
        ));
    }
}
