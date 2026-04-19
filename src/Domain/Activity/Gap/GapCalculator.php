<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\SportType\SportType;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @phpstan-type RawTrackPoint array{
 *     lat: float|int|string,
 *     lon: float|int|string,
 *     ele: float|int|string,
 *     timestamp: \DateTimeInterface|int|string
 * }
 * @phpstan-type NormalizedTrackPoint array{
 *     lat: float,
 *     lon: float,
 *     ele: float,
 *     timestamp: int
 * }
 * @phpstan-type GapSegment array{
 *     from: NormalizedTrackPoint,
 *     to: NormalizedTrackPoint,
 *     distance_m: float,
 *     duration_s: int,
 *     grade: float,
 *     actual_pace_sec_per_km: float,
 *     gap_multiplier: float,
 *     gap_pace_sec_per_km: float
 * }
 * @phpstan-type GapSummary array{
 *     segments: int,
 *     distance_m: float,
 *     duration_s: int,
 *     actual_pace_sec_per_km: ?float,
 *     gap_pace_sec_per_km: ?float,
 *     average_grade: float,
 *     total_adjusted_distance_m: float
 * }
 */
#[Autoconfigure(constructor: 'create')]
final readonly class GapCalculator
{
    private const EARTH_RADIUS_M = 6371000.0;
    private const FLAT_METABOLIC_COST = 3.6;
    private const DEFAULT_GRADE_DISTANCE_WINDOW_M = 50.0;
    private const MIN_ELEVATION_CHANGE_THRESHOLD_M = 0.1;
    private const GRADE_DEAD_ZONE = 0.005;

    private function __construct(
        private int $smoothingWindowSize = 31,
        private float $gradeDistanceWindowInMeters = self::DEFAULT_GRADE_DISTANCE_WINDOW_M,
        private float $minGrade = -0.45,
        private float $maxGrade = 0.45,
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
    ): self {
        return new self(
            smoothingWindowSize: $smoothingWindowSize,
            gradeDistanceWindowInMeters: $gradeDistanceWindowInMeters,
            minGrade: $minGrade,
            maxGrade: $maxGrade,
        );
    }

    /**
     * @param iterable<array<string, mixed>|object> $trackPoints
     *
     * @return GapSummary
     */
    public function calculate(iterable $trackPoints, ?SportType $sportType = null): array
    {
        if (null !== $sportType && !$this->supports($sportType)) {
            return $this->emptySummary();
        }

        $segments = 0;
        $distance = 0.0;
        $duration = 0;
        $weightedGrade = 0.0;
        $adjustedDistance = 0.0;

        foreach ($this->calculateSegments($trackPoints, $sportType) as $segment) {
            ++$segments;
            $distance += $segment['distance_m'];
            $duration += $segment['duration_s'];
            $weightedGrade += $segment['grade'] * $segment['distance_m'];
            $adjustedDistance += $segment['distance_m'] * $segment['gap_multiplier'];
        }

        $actualPace = $distance > 0.0 ? ($duration / $distance) * 1000.0 : null;
        $gapPace = $adjustedDistance > 0.0 ? ($duration / $adjustedDistance) * 1000.0 : null;

        return [
            'segments' => $segments,
            'distance_m' => $distance,
            'duration_s' => $duration,
            'actual_pace_sec_per_km' => $actualPace,
            'gap_pace_sec_per_km' => $gapPace,
            'average_grade' => $distance > 0.0 ? $weightedGrade / $distance : 0.0,
            'total_adjusted_distance_m' => $adjustedDistance,
        ];
    }

    /**
     * @param iterable<array<string, mixed>|object> $trackPoints
     *
     * @return \Generator<int, GapSegment>
     */
    public function calculateSegments(iterable $trackPoints, ?SportType $sportType = null): \Generator
    {
        if (null !== $sportType && !$this->supports($sportType)) {
            return;
        }

        $points = $this->smoothPoints($trackPoints);
        if (\count($points) < 2) {
            return;
        }

        $cumulativeDistances = $this->buildCumulativeDistances($points);

        for ($i = 1; $i < \count($points); ++$i) {
            $from = $points[$i - 1];
            $to = $points[$i];
            $distance = $cumulativeDistances[$i] - $cumulativeDistances[$i - 1];
            $duration = $to['timestamp'] - $from['timestamp'];

            if ($distance <= 0.0 || $duration <= 0) {
                continue;
            }

            $window = $this->calculateWindowMetrics(
                points: $points,
                cumulativeDistances: $cumulativeDistances,
                centerDistance: ($cumulativeDistances[$i - 1] + $cumulativeDistances[$i]) / 2.0,
            );
            if (null === $window) {
                continue;
            }

            $gapMultiplier = $this->gapMultiplier($window['grade']);

            yield [
                'from' => $from,
                'to' => $to,
                'distance_m' => $distance,
                'duration_s' => $duration,
                'grade' => $window['grade'],
                'actual_pace_sec_per_km' => $window['actual_pace_sec_per_km'],
                'gap_multiplier' => $gapMultiplier,
                'gap_pace_sec_per_km' => $window['actual_pace_sec_per_km'] / $gapMultiplier,
            ];
        }
    }

    public function supports(SportType $sportType): bool
    {
        return ActivityType::RUN->getSportTypes()->has($sportType);
    }

    /**
     * @param iterable<array<string, mixed>|object> $trackPoints
     *
     * @return list<?float>
     */
    public function calculatePointGapPaces(iterable $trackPoints, ?SportType $sportType = null): array
    {
        if (null !== $sportType && !$this->supports($sportType)) {
            return [];
        }

        $points = $this->smoothPoints($trackPoints);
        if (\count($points) < 2) {
            return [];
        }

        $cumulativeDistances = $this->buildCumulativeDistances($points);
        $gapPaces = [];

        foreach ($points as $index => $_point) {
            $window = $this->calculateWindowMetrics(
                points: $points,
                cumulativeDistances: $cumulativeDistances,
                centerDistance: $cumulativeDistances[$index],
            );

            $gapPaces[] = null === $window
                ? null
                : $window['actual_pace_sec_per_km'] / $this->gapMultiplier($window['grade']);
        }

        return $gapPaces;
    }

    /**
     * @param iterable<array<string, mixed>|object> $trackPoints
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
        $smoothedPoints = $points;

        foreach ($points as $index => $point) {
            $weightedElevation = 0.0;
            $totalWeight = 0.0;
            $start = max(0, $index - $radius);
            $end = min(\count($points) - 1, $index + $radius);

            for ($neighborIndex = $start; $neighborIndex <= $end; ++$neighborIndex) {
                $distanceFromCenter = abs($neighborIndex - $index);
                $sigma = max(1.0, $this->smoothingWindowSize / 6.0);
                $weight = exp(-(($distanceFromCenter * $distanceFromCenter) / (2.0 * $sigma * $sigma)));
                $weightedElevation += $points[$neighborIndex]['ele'] * $weight;
                $totalWeight += $weight;
            }

            $smoothedPoints[$index]['ele'] = $weightedElevation / $totalWeight;
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

        for ($i = 1; $i < \count($points); ++$i) {
            $cumulativeDistances[$i] = $cumulativeDistances[$i - 1] + $this->haversineDistance(
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
     * @param list<float> $cumulativeDistances
     *
     * @return array{grade: float, actual_pace_sec_per_km: float}|null
     */
    private function calculateWindowMetrics(array $points, array $cumulativeDistances, float $centerDistance): ?array
    {
        $halfWindow = $this->gradeDistanceWindowInMeters / 2.0;
        $trackLength = $cumulativeDistances[\count($cumulativeDistances) - 1];
        $startDistance = max(0.0, $centerDistance - $halfWindow);
        $endDistance = min($trackLength, $centerDistance + $halfWindow);
        $run = $endDistance - $startDistance;
        if ($run <= 0.0) {
            return null;
        }

        $startElevation = $this->interpolateElevationAtDistance($points, $cumulativeDistances, $startDistance);
        $endElevation = $this->interpolateElevationAtDistance($points, $cumulativeDistances, $endDistance);
        $rise = $endElevation - $startElevation;
        $rawGrade = $rise / $run;
        $grade = abs($rise) < self::MIN_ELEVATION_CHANGE_THRESHOLD_M || abs($rawGrade) < self::GRADE_DEAD_ZONE
            ? 0.0
            : $this->clamp($rawGrade, $this->minGrade, $this->maxGrade);

        $startTimestamp = $this->interpolateTimestampAtDistance($points, $cumulativeDistances, $startDistance);
        $endTimestamp = $this->interpolateTimestampAtDistance($points, $cumulativeDistances, $endDistance);
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
     * @param list<float> $cumulativeDistances
     */
    private function interpolateElevationAtDistance(array $points, array $cumulativeDistances, float $targetDistance): float
    {
        $lastIndex = \count($cumulativeDistances) - 1;

        if ($targetDistance <= 0.0) {
            return $points[0]['ele'];
        }

        if ($targetDistance >= $cumulativeDistances[$lastIndex]) {
            return $points[$lastIndex]['ele'];
        }

        for ($i = 1; $i <= $lastIndex; ++$i) {
            if ($targetDistance > $cumulativeDistances[$i]) {
                continue;
            }

            $segmentStartDistance = $cumulativeDistances[$i - 1];
            $segmentEndDistance = $cumulativeDistances[$i];
            $segmentLength = $segmentEndDistance - $segmentStartDistance;
            if ($segmentLength <= 0.0) {
                return $points[$i]['ele'];
            }

            $ratio = ($targetDistance - $segmentStartDistance) / $segmentLength;

            return $points[$i - 1]['ele'] + (($points[$i]['ele'] - $points[$i - 1]['ele']) * $ratio);
        }

        return $points[$lastIndex]['ele'];
    }

    /**
     * @param list<NormalizedTrackPoint> $points
     * @param list<float> $cumulativeDistances
     */
    private function interpolateTimestampAtDistance(array $points, array $cumulativeDistances, float $targetDistance): float
    {
        $lastIndex = \count($cumulativeDistances) - 1;

        if ($targetDistance <= 0.0) {
            return (float) $points[0]['timestamp'];
        }

        if ($targetDistance >= $cumulativeDistances[$lastIndex]) {
            return (float) $points[$lastIndex]['timestamp'];
        }

        for ($i = 1; $i <= $lastIndex; ++$i) {
            if ($targetDistance > $cumulativeDistances[$i]) {
                continue;
            }

            $segmentStartDistance = $cumulativeDistances[$i - 1];
            $segmentEndDistance = $cumulativeDistances[$i];
            $segmentLength = $segmentEndDistance - $segmentStartDistance;
            if ($segmentLength <= 0.0) {
                return (float) $points[$i]['timestamp'];
            }

            $ratio = ($targetDistance - $segmentStartDistance) / $segmentLength;

            return $points[$i - 1]['timestamp'] + (($points[$i]['timestamp'] - $points[$i - 1]['timestamp']) * $ratio);
        }

        return (float) $points[$lastIndex]['timestamp'];
    }

    private function gapMultiplier(float $grade): float
    {
        $grade2 = $grade * $grade;
        $grade3 = $grade2 * $grade;
        $grade4 = $grade3 * $grade;
        $grade5 = $grade4 * $grade;

        $metabolicCost = 155.4 * $grade5
            - 30.4 * $grade4
            - 43.3 * $grade3
            + 46.3 * $grade2
            + 19.5 * $grade
            + self::FLAT_METABOLIC_COST;

        return max(0.1, $metabolicCost / self::FLAT_METABOLIC_COST);
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

        return self::EARTH_RADIUS_M * 2.0 * atan2(sqrt($a), sqrt(1.0 - $this->clamp($a, 0.0, 1.0)));
    }

    /**
     * @param array<string, mixed>|object $trackPoint
     *
     * @return NormalizedTrackPoint
     */
    private function normalizePoint(array|object $trackPoint): array
    {
        $lat = $this->readField($trackPoint, 'lat');
        $lon = $this->readField($trackPoint, 'lon');
        $ele = $this->readField($trackPoint, 'ele');
        $timestamp = $this->readField($trackPoint, 'timestamp');

        return [
            'lat' => (float) $lat,
            'lon' => (float) $lon,
            'ele' => (float) $ele,
            'timestamp' => $this->normalizeTimestamp($timestamp),
        ];
    }

    private function normalizeTimestamp(mixed $timestamp): int
    {
        return match (true) {
            $timestamp instanceof \DateTimeInterface => $timestamp->getTimestamp(),
            is_int($timestamp) => $timestamp,
            is_string($timestamp) && is_numeric($timestamp) => (int) $timestamp,
            is_string($timestamp) => (new \DateTimeImmutable($timestamp))->getTimestamp(),
            default => throw new \InvalidArgumentException('Unsupported timestamp value provided.'),
        };
    }

    /**
     * @param array<string, mixed>|object $trackPoint
     */
    private function readField(array|object $trackPoint, string $field): mixed
    {
        if (is_array($trackPoint) && array_key_exists($field, $trackPoint)) {
            return $trackPoint[$field];
        }

        if (is_object($trackPoint) && isset($trackPoint->{$field})) {
            return $trackPoint->{$field};
        }

        throw new \InvalidArgumentException(sprintf('Track point is missing required field "%s".', $field));
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * @return GapSummary
     */
    private function emptySummary(): array
    {
        return [
            'segments' => 0,
            'distance_m' => 0.0,
            'duration_s' => 0,
            'actual_pace_sec_per_km' => null,
            'gap_pace_sec_per_km' => null,
            'average_grade' => 0.0,
            'total_adjusted_distance_m' => 0.0,
        ];
    }
}
