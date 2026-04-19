<?php

declare(strict_types=1);

namespace App\Domain\Activity\Gap;

use App\Domain\Activity\SportType\SportType;

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
final readonly class GapCalculator
{
    private const EARTH_RADIUS_M = 6371000.0;
    private const FLAT_METABOLIC_COST = 3.6;
    /** @var non-empty-list<SportType> */
    private const array SUPPORTED_SPORT_TYPES = [
        SportType::RUN,
        SportType::TRAIL_RUN,
        SportType::VIRTUAL_RUN,
    ];

    public function __construct(
        private int $smoothingWindowSize = 5,
        private float $minGrade = -0.45,
        private float $maxGrade = 0.45,
    ) {
        if ($this->smoothingWindowSize < 1) {
            throw new \InvalidArgumentException('Smoothing window size must be at least 1.');
        }
        if ($this->minGrade >= $this->maxGrade) {
            throw new \InvalidArgumentException('Minimum grade must be lower than maximum grade.');
        }
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

        $previousPoint = null;

        foreach ($this->smoothPoints($trackPoints) as $point) {
            if (null === $previousPoint) {
                $previousPoint = $point;
                continue;
            }

            $distance = $this->haversineDistance(
                $previousPoint['lat'],
                $previousPoint['lon'],
                $point['lat'],
                $point['lon'],
            );

            $duration = $point['timestamp'] - $previousPoint['timestamp'];
            if ($distance <= 0.0 || $duration <= 0) {
                $previousPoint = $point;
                continue;
            }

            $rise = $point['ele'] - $previousPoint['ele'];
            $grade = $this->clamp($rise / $distance, $this->minGrade, $this->maxGrade);
            $actualPace = ($duration / $distance) * 1000.0;
            $gapMultiplier = $this->gapMultiplier($grade);

            yield [
                'from' => $previousPoint,
                'to' => $point,
                'distance_m' => $distance,
                'duration_s' => $duration,
                'grade' => $grade,
                'actual_pace_sec_per_km' => $actualPace,
                'gap_multiplier' => $gapMultiplier,
                'gap_pace_sec_per_km' => $actualPace / $gapMultiplier,
            ];

            $previousPoint = $point;
        }
    }

    public function supports(SportType $sportType): bool
    {
        return in_array($sportType, self::SUPPORTED_SPORT_TYPES, true);
    }

    /**
     * @param iterable<array<string, mixed>|object> $trackPoints
     *
     * @return \Generator<int, NormalizedTrackPoint>
     */
    private function smoothPoints(iterable $trackPoints): \Generator
    {
        $window = [];
        $elevationSum = 0.0;

        foreach ($trackPoints as $trackPoint) {
            $normalizedPoint = $this->normalizePoint($trackPoint);
            $window[] = $normalizedPoint;
            $elevationSum += $normalizedPoint['ele'];

            if (\count($window) > $this->smoothingWindowSize) {
                /** @var NormalizedTrackPoint $removedPoint */
                $removedPoint = array_shift($window);
                $elevationSum -= $removedPoint['ele'];
            }

            $point = $window[\count($window) - 1];
            $point['ele'] = $elevationSum / \count($window);

            yield $point;
        }
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

        return max(0.01, $metabolicCost / self::FLAT_METABOLIC_COST);
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

        $a = $this->clamp($a, 0.0, 1.0);

        return self::EARTH_RADIUS_M * 2.0 * atan2(sqrt($a), sqrt(1.0 - $a));
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
}
