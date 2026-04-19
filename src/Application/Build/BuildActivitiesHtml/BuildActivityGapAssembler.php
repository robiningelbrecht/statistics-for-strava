<?php

declare(strict_types=1);

namespace App\Application\Build\BuildActivitiesHtml;

use App\Domain\Activity\Activity;
use App\Domain\Activity\Gap\GapCalculator;
use App\Domain\Activity\Split\ActivitySplit;
use App\Domain\Activity\Split\ActivitySplits;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

/**
 * @phpstan-type BuildActivityGapTrackPoint array{lat: float, lon: float, ele: float, timestamp: int}
 * @phpstan-type BuildActivityGapSegment array{
 *     from: BuildActivityGapTrackPoint,
 *     to: BuildActivityGapTrackPoint,
 *     distance_m: float,
 *     duration_s: int,
 *     grade: float,
 *     actual_pace_sec_per_km: float,
 *     gap_multiplier: float,
 *     gap_pace_sec_per_km: float
 * }
 */
final readonly class BuildActivityGapAssembler
{
    public function __construct(
        private GapCalculator $gapCalculator = new GapCalculator(),
    ) {
    }

    public function for(
        Activity $activity,
        ActivityStreams $streams,
        ActivitySplits $metricSplits,
        ActivitySplits $imperialSplits,
    ): ?BuildActivityGapData {
        if (!$this->gapCalculator->supports($activity->getSportType())) {
            return null;
        }

        $trackPoints = $this->buildTrackPoints($streams);
        if ([] === $trackPoints) {
            return null;
        }

        $summary = $this->gapCalculator->calculate($trackPoints, $activity->getSportType());
        if (null === $summary['gap_pace_sec_per_km']) {
            return null;
        }

        /** @var list<BuildActivityGapSegment> $segments */
        $segments = iterator_to_array($this->gapCalculator->calculateSegments($trackPoints, $activity->getSportType()), false);
        if ([] === $segments) {
            return null;
        }

        return new BuildActivityGapData(
            overallGapPaceInSecondsPerKm: SecPerKm::from($summary['gap_pace_sec_per_km']),
            metricSplits: $this->mapSegmentsToSplits($segments, $metricSplits, UnitSystem::METRIC),
            imperialSplits: $this->mapSegmentsToSplits($segments, $imperialSplits, UnitSystem::IMPERIAL),
            profileChartData: $this->buildProfileChartData($segments),
        );
    }

    /**
     * @return list<BuildActivityGapTrackPoint>
     */
    private function buildTrackPoints(ActivityStreams $streams): array
    {
        $latLng = $streams->filterOnType(StreamType::LAT_LNG)?->getData() ?? [];
        $altitude = $streams->filterOnType(StreamType::ALTITUDE)?->getData() ?? [];
        $time = $streams->filterOnType(StreamType::TIME)?->getData() ?? [];
        if ([] === $latLng || [] === $altitude || [] === $time) {
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
            if (!is_array($coordinate) || 2 !== count($coordinate)) {
                continue;
            }

            $trackPoints[] = [
                'lat' => (float) $coordinate[0],
                'lon' => (float) $coordinate[1],
                'ele' => (float) $altitude[$i],
                'timestamp' => (int) $time[$i],
            ];
        }

        return $trackPoints;
    }

    /**
     * @param list<BuildActivityGapSegment> $segments
     *
     * @return array<int, BuildActivityGapSplitData>
     */
    private function mapSegmentsToSplits(array $segments, ActivitySplits $splits, UnitSystem $unitSystem): array
    {
        $splitItems = $splits->toArray();
        if ([] === $splitItems) {
            return [];
        }

        $currentSplitIndex = 0;
        $distanceInCurrentSplit = 0.0;
        $durationInCurrentSplit = 0.0;
        $adjustedDistanceInCurrentSplit = 0.0;
        $mappedSplits = [];

        foreach ($segments as $segment) {
            $remainingDistance = $segment['distance_m'];
            $remainingDuration = (float) $segment['duration_s'];
            $remainingAdjustedDistance = $segment['distance_m'] * $segment['gap_multiplier'];

            while ($remainingDistance > 0.0 && isset($splitItems[$currentSplitIndex])) {
                /** @var ActivitySplit $split */
                $split = $splitItems[$currentSplitIndex];
                $targetDistance = $split->getDistance()->toMeter()->toFloat();
                $remainingInSplit = $targetDistance - $distanceInCurrentSplit;
                if ($remainingInSplit <= 0.00001) {
                    $this->finalizeSplit($mappedSplits, $split, $unitSystem, $targetDistance, $distanceInCurrentSplit, $durationInCurrentSplit, $adjustedDistanceInCurrentSplit);
                    ++$currentSplitIndex;
                    $distanceInCurrentSplit = 0.0;
                    $durationInCurrentSplit = 0.0;
                    $adjustedDistanceInCurrentSplit = 0.0;
                    continue;
                }

                $distancePortion = min($remainingDistance, $remainingInSplit);
                $ratio = $distancePortion / $remainingDistance;
                $durationPortion = $remainingDuration * $ratio;
                $adjustedDistancePortion = $remainingAdjustedDistance * $ratio;

                $distanceInCurrentSplit += $distancePortion;
                $durationInCurrentSplit += $durationPortion;
                $adjustedDistanceInCurrentSplit += $adjustedDistancePortion;

                $remainingDistance -= $distancePortion;
                $remainingDuration -= $durationPortion;
                $remainingAdjustedDistance -= $adjustedDistancePortion;

                if ($distanceInCurrentSplit >= $targetDistance - 0.00001) {
                    $this->finalizeSplit($mappedSplits, $split, $unitSystem, $targetDistance, $distanceInCurrentSplit, $durationInCurrentSplit, $adjustedDistanceInCurrentSplit);
                    ++$currentSplitIndex;
                    $distanceInCurrentSplit = 0.0;
                    $durationInCurrentSplit = 0.0;
                    $adjustedDistanceInCurrentSplit = 0.0;
                }
            }

            if (!isset($splitItems[$currentSplitIndex])) {
                break;
            }
        }

        return $mappedSplits;
    }

    /**
     * @param array<int, BuildActivityGapSplitData> $mappedSplits
     */
    private function finalizeSplit(
        array &$mappedSplits,
        ActivitySplit $split,
        UnitSystem $unitSystem,
        float $targetDistanceInMeters,
        float $distanceInCurrentSplit,
        float $durationInCurrentSplit,
        float $adjustedDistanceInCurrentSplit,
    ): void {
        if ($distanceInCurrentSplit < $targetDistanceInMeters - 0.00001 || $adjustedDistanceInCurrentSplit <= 0.0) {
            return;
        }

        $paceMultiplier = UnitSystem::METRIC === $unitSystem ? 1000.0 : Mile::from(1)->toMeter()->toFloat();
        $mappedSplits[$split->getSplitNumber()] = new BuildActivityGapSplitData(
            gapPaceInSeconds: SecPerKm::from(($durationInCurrentSplit / $adjustedDistanceInCurrentSplit) * $paceMultiplier),
        );
    }

    /**
     * @param list<BuildActivityGapSegment> $segments
     *
     * @return list<int>
     */
    private function buildProfileChartData(array $segments): array
    {
        if ([] === $segments) {
            return [];
        }

        $profileChartData = [(int) round($segments[0]['gap_pace_sec_per_km'])];
        foreach ($segments as $segment) {
            $profileChartData[] = (int) round($segment['gap_pace_sec_per_km']);
        }

        return $profileChartData;
    }
}
