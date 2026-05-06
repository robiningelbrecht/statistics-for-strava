<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

use App\Domain\Activity\Math;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @phpstan-type NormalizedTrackPoint array{
 *     lat: float,
 *     lon: float,
 *     ele: float,
 *     timestamp: int
 * }
 */
final readonly class GapCalculator
{
    private const float EARTH_RADIUS_M = 6371000.0;
    private const float DEFAULT_GRADE_DISTANCE_WINDOW_M = 200.0;
    private const float MIN_ELEVATION_CHANGE_THRESHOLD_M = 0.1;
    private const float GRADE_DEAD_ZONE = 0.005;

    private function __construct(
        private int $smoothingWindowSize = 31,
        private float $gradeDistanceWindowInMeters = self::DEFAULT_GRADE_DISTANCE_WINDOW_M,
        private float $minGrade = -0.45,
        private float $maxGrade = 0.45,
        private GapAdjustmentModel $adjustmentModel = new StravaLikeGapAdjustmentModel(),
    ) {
        if ($this->smoothingWindowSize < 1) {
            throw new \InvalidArgumentException('Smoothing window size must be at least 1.');
        }
        if ($this->gradeDistanceWindowInMeters <= 0.0) {
            throw new \InvalidArgumentException('Grade distance window must be greater than 0.');
        }
        if ($this->minGrade >= $this->maxGrade) {
            throw new \InvalidArgumentException('Minimum grade must be lower than maximum grade.');
        }
    }

    public static function create(
        int $smoothingWindowSize = 25,
        float $gradeDistanceWindowInMeters = self::DEFAULT_GRADE_DISTANCE_WINDOW_M,
        float $minGrade = -0.45,
        float $maxGrade = 0.45,
        ?GapAdjustmentModel $adjustmentModel = null,
    ): self {
        return new self(
            smoothingWindowSize: $smoothingWindowSize,
            gradeDistanceWindowInMeters: $gradeDistanceWindowInMeters,
            minGrade: $minGrade,
            maxGrade: $maxGrade,
            adjustmentModel: $adjustmentModel ?? new StravaLikeGapAdjustmentModel(),
        );
    }

    /**
     * @param iterable<array<string, mixed>> $trackPoints
     */
    public function calculate(iterable $trackPoints): Gap
    {
        $segments = 0;
        $distance = 0.0;
        $duration = 0;
        $weightedGrade = 0.0;
        $adjustedDistance = 0.0;

        foreach ($this->calculateSegments($trackPoints) as $segment) {
            ++$segments;
            $distance += $segment->getDistanceInMeters();
            $duration += $segment->getDurationInSeconds();
            $weightedGrade += $segment->getGrade() * $segment->getDistanceInMeters();
            $adjustedDistance += $segment->getDistanceInMeters() * $segment->getGapMultiplier();
        }

        return Gap::create(
            segmentCount: $segments,
            distanceInMeters: $distance,
            durationInSeconds: $duration,
            actualPaceInSecondsPerKm: $distance > 0.0 ? ($duration / $distance) * 1000.0 : null,
            gapPaceInSecondsPerKm: $adjustedDistance > 0.0 ? ($duration / $adjustedDistance) * 1000.0 : null,
            averageGrade: $distance > 0.0 ? $weightedGrade / $distance : 0.0,
            totalAdjustedDistanceInMeters: $adjustedDistance,
        );
    }

    /**
     * @param iterable<array<string, mixed>> $trackPoints
     *
     * @return \Generator<int, GapSegment>
     */
    public function calculateSegments(iterable $trackPoints): \Generator
    {
        $points = $this->smoothPoints($trackPoints);
        $pointCount = \count($points);
        if ($pointCount < 2) {
            return;
        }

        $cumulativeDistances = $this->buildCumulativeDistances($points);
        $lastIndex = $pointCount - 1;
        $trackLength = $cumulativeDistances[$lastIndex];

        for ($i = 1; $i < $pointCount; ++$i) {
            $from = $points[$i - 1];
            $to = $points[$i];
            $distance = $cumulativeDistances[$i] - $cumulativeDistances[$i - 1];
            $duration = $to['timestamp'] - $from['timestamp'];
            if ($distance <= 0.0) {
                continue;
            }
            if ($duration <= 0) {
                continue;
            }

            $actualPaceInSecondsPerKm = ($duration / $distance) * 1000.0;
            $grade = $this->resolveSegmentGrade(
                from: $from,
                to: $to,
                points: $points,
                cumulativeDistances: $cumulativeDistances,
                centerDistance: ($cumulativeDistances[$i - 1] + $cumulativeDistances[$i]) / 2.0,
                trackLength: $trackLength,
                lastIndex: $lastIndex,
            );
            $gapMultiplier = $this->adjustmentModel->adjustmentFactor($grade);

            yield GapSegment::create(
                distanceInMeters: $distance,
                durationInSeconds: $duration,
                grade: $grade,
                actualPaceInSecondsPerKm: $actualPaceInSecondsPerKm,
                gapMultiplier: $gapMultiplier,
                gapPaceInSecondsPerKm: $actualPaceInSecondsPerKm / $gapMultiplier,
            );
        }
    }

    /**
     * @param iterable<array<string, mixed>> $trackPoints
     *
     * @return list<?float>
     */
    public function calculatePointGapPaces(iterable $trackPoints): array
    {
        $points = $this->smoothPoints($trackPoints);
        $pointCount = \count($points);
        if ($pointCount < 2) {
            return [];
        }

        $cumulativeDistances = $this->buildCumulativeDistances($points);
        $lastIndex = $pointCount - 1;
        $trackLength = $cumulativeDistances[$lastIndex];
        $gapPaces = [];

        foreach (array_keys($points) as $index) {
            $window = $this->calculateWindowMetrics(
                points: $points,
                cumulativeDistances: $cumulativeDistances,
                centerDistance: $cumulativeDistances[$index],
                trackLength: $trackLength,
                lastIndex: $lastIndex,
            );

            $gapPaces[] = null === $window
                ? null
                : $window['actual_pace_sec_per_km'] / $this->adjustmentModel->adjustmentFactor($window['grade']);
        }

        return $gapPaces;
    }

    /**
     * @param iterable<array<string, mixed>> $trackPoints
     *
     * @return list<NormalizedTrackPoint>
     */
    private function smoothPoints(iterable $trackPoints): array
    {
        $points = [];

        foreach ($trackPoints as $trackPoint) {
            $points[] = $this->normalizePoint($trackPoint);
        }

        if ([] === $points) {
            return [];
        }

        $radius = intdiv($this->smoothingWindowSize, 2);
        $sigma = max(1.0, $this->smoothingWindowSize / 6.0);
        $twoSigmaSquared = 2.0 * $sigma * $sigma;
        $weights = [];
        for ($d = 0; $d <= $radius; ++$d) {
            $weights[$d] = exp(-($d * $d) / $twoSigmaSquared);
        }

        $pointCount = \count($points);
        $smoothedPoints = $points;

        foreach (array_keys($points) as $index) {
            $weightedElevation = 0.0;
            $totalWeight = 0.0;
            $start = max(0, $index - $radius);
            $end = min($pointCount - 1, $index + $radius);

            for ($neighborIndex = $start; $neighborIndex <= $end; ++$neighborIndex) {
                $weight = $weights[abs($neighborIndex - $index)];
                $weightedElevation += $points[$neighborIndex]['ele'] * $weight;
                $totalWeight += $weight;
            }

            $smoothedPoints[$index] = [
                ...$smoothedPoints[$index],
                'ele' => $weightedElevation / $totalWeight,
            ];
        }

        return $smoothedPoints;
    }

    /**
     * @param list<NormalizedTrackPoint> $points
     *
     * @return list<float>
     */
    private function buildCumulativeDistances(array $points): array
    {
        $cumulativeDistances = [0.0];
        $pointCount = \count($points);

        for ($i = 1; $i < $pointCount; ++$i) {
            $cumulativeDistances[] = $cumulativeDistances[$i - 1] + $this->haversineDistance(
                $points[$i - 1]['lat'],
                $points[$i - 1]['lon'],
                $points[$i]['lat'],
                $points[$i]['lon'],
            );
        }

        return $cumulativeDistances;
    }

    /**
     * @param list<NormalizedTrackPoint> $points
     * @param list<float>                $cumulativeDistances
     *
     * @return array{grade: float, actual_pace_sec_per_km: float}|null
     */
    private function calculateWindowMetrics(array $points, array $cumulativeDistances, float $centerDistance, float $trackLength, int $lastIndex): ?array
    {
        $halfWindow = $this->gradeDistanceWindowInMeters / 2.0;
        $startDistance = max(0.0, $centerDistance - $halfWindow);
        $endDistance = min($trackLength, $centerDistance + $halfWindow);
        $run = $endDistance - $startDistance;
        if ($run <= 0.0) {
            return null;
        }

        [$startElevation, $startTimestamp] = $this->interpolateAtDistance($points, $cumulativeDistances, $startDistance, $lastIndex);
        [$endElevation, $endTimestamp] = $this->interpolateAtDistance($points, $cumulativeDistances, $endDistance, $lastIndex);

        $rise = $endElevation - $startElevation;
        $rawGrade = $rise / $run;
        $grade = abs($rise) < self::MIN_ELEVATION_CHANGE_THRESHOLD_M || abs($rawGrade) < self::GRADE_DEAD_ZONE
            ? 0.0
            : Math::clamp($rawGrade, $this->minGrade, $this->maxGrade);

        $duration = $endTimestamp - $startTimestamp;
        if ($duration <= 0.0) {
            return null;
        }

        return [
            'grade' => $grade,
            'actual_pace_sec_per_km' => ($duration / $run) * 1000.0,
        ];
    }

    /**
     * @param list<NormalizedTrackPoint> $points
     * @param list<float>                $cumulativeDistances
     *
     * @return array{float, float} [elevation, timestamp]
     */
    private function interpolateAtDistance(array $points, array $cumulativeDistances, float $targetDistance, int $lastIndex): array
    {
        if ($targetDistance <= 0.0) {
            return [$points[0]['ele'], (float) $points[0]['timestamp']];
        }

        if ($targetDistance >= $cumulativeDistances[$lastIndex]) {
            return [$points[$lastIndex]['ele'], (float) $points[$lastIndex]['timestamp']];
        }

        $i = $this->findSegmentIndex($cumulativeDistances, $targetDistance, $lastIndex);
        $segmentLength = $cumulativeDistances[$i] - $cumulativeDistances[$i - 1];
        $ratio = ($targetDistance - $cumulativeDistances[$i - 1]) / $segmentLength;

        return [
            $points[$i - 1]['ele'] + (($points[$i]['ele'] - $points[$i - 1]['ele']) * $ratio),
            $points[$i - 1]['timestamp'] + (($points[$i]['timestamp'] - $points[$i - 1]['timestamp']) * $ratio),
        ];
    }

    /**
     * @param list<float> $cumulativeDistances
     */
    private function findSegmentIndex(array $cumulativeDistances, float $targetDistance, int $lastIndex): int
    {
        $low = 1;
        $high = $lastIndex;

        while ($low < $high) {
            $mid = $low + intdiv($high - $low, 2);
            if ($cumulativeDistances[$mid] < $targetDistance) {
                $low = $mid + 1;
            } else {
                $high = $mid;
            }
        }

        return $low;
    }

    /**
     * @param NormalizedTrackPoint       $from
     * @param NormalizedTrackPoint       $to
     * @param list<NormalizedTrackPoint> $points
     * @param list<float>                $cumulativeDistances
     */
    private function resolveSegmentGrade(
        array $from,
        array $to,
        array $points,
        array $cumulativeDistances,
        float $centerDistance,
        float $trackLength,
        int $lastIndex,
    ): float {
        return $this->calculateWindowMetrics(
            points: $points,
            cumulativeDistances: $cumulativeDistances,
            centerDistance: $centerDistance,
            trackLength: $trackLength,
            lastIndex: $lastIndex,
        )['grade'] ?? 0.0;
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $sinDeltaLat = sin($deltaLat / 2.0);
        $sinDeltaLon = sin($deltaLon / 2.0);

        $a = $sinDeltaLat * $sinDeltaLat
            + cos($lat1Rad) * cos($lat2Rad) * $sinDeltaLon * $sinDeltaLon;

        return self::EARTH_RADIUS_M * 2.0 * atan2(sqrt($a), sqrt(1.0 - Math::clamp($a, 0.0, 1.0)));
    }

    /**
     * @param array<string, mixed> $trackPoint
     *
     * @return NormalizedTrackPoint
     */
    private function normalizePoint(array $trackPoint): array
    {
        return [
            'lat' => (float) ($trackPoint['lat'] ?? throw new \InvalidArgumentException('Track point is missing required field "lat".')),
            'lon' => (float) ($trackPoint['lon'] ?? throw new \InvalidArgumentException('Track point is missing required field "lon".')),
            'ele' => (float) ($trackPoint['ele'] ?? throw new \InvalidArgumentException('Track point is missing required field "ele".')),
            'timestamp' => match (true) {
                ($ts = $trackPoint['timestamp'] ?? null) instanceof SerializableDateTime => $ts->getTimestamp(),
                is_int($ts) => $ts,
                is_string($ts) && is_numeric($ts) => (int) $ts,
                is_string($ts) => SerializableDateTime::fromString($ts)->getTimestamp(),
                null === $ts => throw new \InvalidArgumentException('Track point is missing required field "timestamp".'),
                default => throw new \InvalidArgumentException('Unsupported timestamp value provided.'),
            },
        ];
    }
}
