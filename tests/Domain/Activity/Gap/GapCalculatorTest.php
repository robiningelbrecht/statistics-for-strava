<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Gap;

use App\Domain\Activity\Gap\GapCalculator;
use App\Domain\Activity\SportType\SportType;
use PHPUnit\Framework\TestCase;

final class GapCalculatorTest extends TestCase
{
    public function testItCalculatesGapSummaryFromGeneratorInput(): void
    {
        $calculator = GapCalculator::create(smoothingWindowSize: 3);

        $summary = $calculator->calculate($this->trackPointsWithModerateClimb());

        self::assertSame(3, $summary['segments']);
        self::assertGreaterThan(330.0, $summary['distance_m']);
        self::assertLessThan(336.0, $summary['distance_m']);
        self::assertSame(120, $summary['duration_s']);
        self::assertGreaterThan(350.0, $summary['actual_pace_sec_per_km']);
        self::assertLessThan(370.0, $summary['actual_pace_sec_per_km']);
        self::assertTrue($summary['gap_pace_sec_per_km'] <= $summary['actual_pace_sec_per_km']);
        self::assertGreaterThan(0.0, $summary['average_grade']);
        self::assertGreaterThan($summary['distance_m'], $summary['total_adjusted_distance_m']);
    }

    public function testItRewardsSlightDownhillButPenalizesSteepDownhill(): void
    {
        $calculator = GapCalculator::create(smoothingWindowSize: 1);

        $segments = iterator_to_array($calculator->calculateSegments($this->downhillTrackPoints()), false);

        self::assertCount(2, $segments);
        self::assertLessThan(1.0, $segments[0]['gap_multiplier']);
        self::assertGreaterThan(1.0, $segments[1]['gap_multiplier']);
        self::assertGreaterThan($segments[1]['gap_pace_sec_per_km'], $segments[0]['gap_pace_sec_per_km']);
    }

    public function testItSkipsZeroDistanceAndNonIncreasingTimestamps(): void
    {
        $calculator = GapCalculator::create(smoothingWindowSize: 1);

        $segments = iterator_to_array($calculator->calculateSegments([
            [
                'lat' => 51.0,
                'lon' => 4.0,
                'ele' => 10.0,
                'timestamp' => 1700000000,
            ],
            [
                'lat' => 51.0,
                'lon' => 4.0,
                'ele' => 20.0,
                'timestamp' => 1700000030,
            ],
            [
                'lat' => 51.0009,
                'lon' => 4.0,
                'ele' => 25.0,
                'timestamp' => 1700000030,
            ],
            [
                'lat' => 51.0018,
                'lon' => 4.0,
                'ele' => 30.0,
                'timestamp' => 1700000060,
            ],
        ]), false);

        self::assertCount(1, $segments);
        self::assertSame(30, $segments[0]['duration_s']);
    }

    public function testItSmoothsElevationSpikesBeforeCalculatingGrade(): void
    {
        $unsmoothedCalculator = GapCalculator::create(smoothingWindowSize: 1);
        $smoothedCalculator = GapCalculator::create(smoothingWindowSize: 5);

        $unsmoothedSegments = iterator_to_array($unsmoothedCalculator->calculateSegments($this->trackPointsWithElevationSpike()), false);
        $smoothedSegments = iterator_to_array($smoothedCalculator->calculateSegments($this->trackPointsWithElevationSpike()), false);

        self::assertCount(\count($unsmoothedSegments), $smoothedSegments);
        self::assertGreaterThan(
            max(array_map(static fn (array $segment): float => abs($segment['grade']), $smoothedSegments)),
            max(array_map(static fn (array $segment): float => abs($segment['grade']), $unsmoothedSegments)),
        );
    }

    public function testItUsesDistanceWindowSoDenseTimestampSamplingStaysStable(): void
    {
        $calculator = GapCalculator::create(smoothingWindowSize: 3);

        $sparseSummary = $calculator->calculate($this->trackPointsWithModerateClimb());
        $denseSummary = $calculator->calculate($this->densifyTrack($this->trackPointsWithModerateClimbList(), 4));

        self::assertNotNull($sparseSummary['gap_pace_sec_per_km']);
        self::assertNotNull($denseSummary['gap_pace_sec_per_km']);
        self::assertEqualsWithDelta(
            $sparseSummary['gap_pace_sec_per_km'],
            $denseSummary['gap_pace_sec_per_km'],
            40.0,
        );
        self::assertEqualsWithDelta(
            $sparseSummary['average_grade'],
            $denseSummary['average_grade'],
            0.01,
        );
    }

    public function testItKeepsEdgeSegmentsWhenDistanceWindowOverlapsTrackBoundaries(): void
    {
        $calculator = GapCalculator::create(smoothingWindowSize: 3);

        $segments = iterator_to_array($calculator->calculateSegments($this->shortTrackPoints()), false);

        self::assertCount(2, $segments);
        self::assertGreaterThan(0.0, $segments[0]['grade']);
        self::assertGreaterThan(0.0, $segments[1]['grade']);
    }

    public function testItOnlyAppliesToRunningSportTypes(): void
    {
        $calculator = GapCalculator::create();

        self::assertTrue($calculator->supports(SportType::RUN));
        self::assertTrue($calculator->supports(SportType::TRAIL_RUN));
        self::assertTrue($calculator->supports(SportType::VIRTUAL_RUN));
        self::assertFalse($calculator->supports(SportType::RIDE));

        self::assertSame(
            [
                'segments' => 0,
                'distance_m' => 0.0,
                'duration_s' => 0,
                'actual_pace_sec_per_km' => null,
                'gap_pace_sec_per_km' => null,
                'average_grade' => 0.0,
                'total_adjusted_distance_m' => 0.0,
            ],
            $calculator->calculate($this->trackPointsWithModerateClimb(), SportType::RIDE)
        );
        self::assertSame(
            [],
            iterator_to_array($calculator->calculateSegments($this->trackPointsWithModerateClimb(), SportType::RIDE), false)
        );
    }

    /**
     * @return \Generator<int, array<string, float|int|string>>
     */
    private function trackPointsWithModerateClimb(): \Generator
    {
        yield [
            'lat' => 51.0000,
            'lon' => 4.0000,
            'ele' => 12.0,
            'timestamp' => '2026-04-18T08:00:00+00:00',
        ];
        yield [
            'lat' => 51.0010,
            'lon' => 4.0000,
            'ele' => 16.0,
            'timestamp' => '2026-04-18T08:00:40+00:00',
        ];
        yield [
            'lat' => 51.0020,
            'lon' => 4.0000,
            'ele' => 18.0,
            'timestamp' => '2026-04-18T08:01:20+00:00',
        ];
        yield [
            'lat' => 51.0030,
            'lon' => 4.0000,
            'ele' => 23.0,
            'timestamp' => '2026-04-18T08:02:00+00:00',
        ];
    }

    /**
     * @return list<array<string, float|int>>
     */
    private function downhillTrackPoints(): array
    {
        return [
            [
                'lat' => 51.0000,
                'lon' => 4.0000,
                'ele' => 30.0,
                'timestamp' => 1700000000,
            ],
            [
                'lat' => 51.0010,
                'lon' => 4.0000,
                'ele' => 19.0,
                'timestamp' => 1700000040,
            ],
            [
                'lat' => 51.0020,
                'lon' => 4.0000,
                'ele' => -41.0,
                'timestamp' => 1700000080,
            ],
        ];
    }

    /**
     * @return list<array<string, float|int>>
     */
    private function trackPointsWithElevationSpike(): array
    {
        return [
            ['lat' => 51.0000, 'lon' => 4.0000, 'ele' => 10.0, 'timestamp' => 0],
            ['lat' => 51.0002, 'lon' => 4.0000, 'ele' => 10.5, 'timestamp' => 8],
            ['lat' => 51.0004, 'lon' => 4.0000, 'ele' => 45.0, 'timestamp' => 16],
            ['lat' => 51.0006, 'lon' => 4.0000, 'ele' => 11.0, 'timestamp' => 24],
            ['lat' => 51.0008, 'lon' => 4.0000, 'ele' => 11.5, 'timestamp' => 32],
        ];
    }

    /**
     * @return list<array<string, float|int|string>>
     */
    private function trackPointsWithModerateClimbList(): array
    {
        return iterator_to_array($this->trackPointsWithModerateClimb(), false);
    }

    /**
     * @param list<array<string, float|int|string>> $trackPoints
     *
     * @return list<array<string, float|int>>
     */
    private function densifyTrack(array $trackPoints, int $subdivisionsPerSegment): array
    {
        $denseTrack = [];

        for ($i = 0; $i < \count($trackPoints) - 1; ++$i) {
            $from = $trackPoints[$i];
            $to = $trackPoints[$i + 1];

            for ($step = 0; $step < $subdivisionsPerSegment; ++$step) {
                $ratio = $step / $subdivisionsPerSegment;
                $denseTrack[] = [
                    'lat' => (float) $from['lat'] + (((float) $to['lat'] - (float) $from['lat']) * $ratio),
                    'lon' => (float) $from['lon'] + (((float) $to['lon'] - (float) $from['lon']) * $ratio),
                    'ele' => (float) $from['ele'] + (((float) $to['ele'] - (float) $from['ele']) * $ratio),
                    'timestamp' => (int) round($this->normalizeTestTimestamp($from['timestamp']) + (($this->normalizeTestTimestamp($to['timestamp']) - $this->normalizeTestTimestamp($from['timestamp'])) * $ratio)),
                ];
            }
        }

        $lastPoint = $trackPoints[\count($trackPoints) - 1];
        $denseTrack[] = [
            'lat' => (float) $lastPoint['lat'],
            'lon' => (float) $lastPoint['lon'],
            'ele' => (float) $lastPoint['ele'],
            'timestamp' => $this->normalizeTestTimestamp($lastPoint['timestamp']),
        ];

        return $denseTrack;
    }

    /**
     * @return list<array<string, float|int>>
     */
    private function shortTrackPoints(): array
    {
        return [
            ['lat' => 51.0000, 'lon' => 4.0000, 'ele' => 10.0, 'timestamp' => 0],
            ['lat' => 51.0001, 'lon' => 4.0000, 'ele' => 11.0, 'timestamp' => 6],
            ['lat' => 51.0002, 'lon' => 4.0000, 'ele' => 12.0, 'timestamp' => 12],
        ];
    }

    private function normalizeTestTimestamp(float|int|string $timestamp): int
    {
        if (is_int($timestamp)) {
            return $timestamp;
        }

        if (is_string($timestamp) && !is_numeric($timestamp)) {
            return (new \DateTimeImmutable($timestamp))->getTimestamp();
        }

        return (int) $timestamp;
    }
}
