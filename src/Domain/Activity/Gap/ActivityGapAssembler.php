<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

use App\Domain\Activity\Activity;
use App\Domain\Activity\Split\ActivitySplit;
use App\Domain\Activity\Split\ActivitySplits;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

/**
 * @phpstan-type ActivityGapTrackPoint array{lat: float, lon: float, ele: float, timestamp: int}
 */
final readonly class ActivityGapAssembler
{
    public function for(
        Activity $activity,
        ActivityStreams $streams,
        ActivitySplits $metricSplits,
        ActivitySplits $imperialSplits,
    ): ?ActivityGap {
        $gapCalculator = GapCalculator::create();

        if (!$gapCalculator->supports($activity->getSportType())) {
            return null;
        }

        $trackPoints = $this->buildTrackPoints($streams);
        if ([] === $trackPoints) {
            return null;
        }

        $summary = $gapCalculator->calculate($trackPoints, $activity->getSportType());
        if (null === $summary->getGapPaceInSecondsPerKm()) {
            return null;
        }

        /** @var list<GapSegment> $segments */
        $segments = iterator_to_array($gapCalculator->calculateSegments($trackPoints, $activity->getSportType()), false);
        if ([] === $segments) {
            return null;
        }

        return new ActivityGap(
            overallGapPaceInSecondsPerKm: SecPerKm::from($summary->getGapPaceInSecondsPerKm()),
            metricSplits: $this->mapSegmentsToSplits($segments, $metricSplits, UnitSystem::METRIC),
            imperialSplits: $this->mapSegmentsToSplits($segments, $imperialSplits, UnitSystem::IMPERIAL),
        );
    }

    /**
     * @return list<ActivityGapTrackPoint>
     */
    private function buildTrackPoints(ActivityStreams $streams): array
    {
        $latLng = $streams->filterOnType(StreamType::LAT_LNG)?->getData() ?? [];
        $altitude = $streams->filterOnType(StreamType::ALTITUDE)?->getData() ?? [];
        $time = $streams->filterOnType(StreamType::TIME)?->getData() ?? [];
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
            ];
        }

        return $trackPoints;
    }

    /**
     * @param list<GapSegment> $segments
     *
     * @return array<int, ActivityGapSplit>
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
            $remainingDistance = $segment->getDistanceInMeters();
            $remainingDuration = (float) $segment->getDurationInSeconds();
            $remainingAdjustedDistance = $segment->getDistanceInMeters() * $segment->getGapMultiplier();

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
     * @param array<int, ActivityGapSplit> $mappedSplits
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
        $mappedSplits[$split->getSplitNumber()] = new ActivityGapSplit(
            gapPaceInSeconds: SecPerKm::from(($durationInCurrentSplit / $adjustedDistanceInCurrentSplit) * $paceMultiplier),
        );
    }
}
