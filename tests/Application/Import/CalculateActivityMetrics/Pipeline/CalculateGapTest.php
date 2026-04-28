<?php

namespace App\Tests\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Application\Import\CalculateActivityMetrics\Pipeline\CalculateGap;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Split\ActivitySplitBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\SpyOutput;

class CalculateGapTest extends ContainerTestCase
{
    private CalculateGap $calculateGap;
    private ActivitySplitRepository $activitySplitRepository;
    private ActivityStreamRepository $activityStreamRepository;
    private ActivityRepository $activityRepository;

    public function testProcessCalculatesGapForRunActivity(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-1');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityId, [1000.0, 1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNotNull($metricSplits->toArray()[0]->getGapPaceInSecondsPerKm());
        $this->assertNotNull($metricSplits->toArray()[1]->getGapPaceInSecondsPerKm());
    }

    public function testProcessCalculatesGapForTrailRunActivity(): void
    {
        $activityId = ActivityId::fromUnprefixed('trail-run-1');
        $this->addActivity($activityId, SportType::TRAIL_RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityId, [1000.0, 1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNotNull($metricSplits->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessSkipsActivitiesAlreadyWithGap(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-already-done');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());

        $existingGap = SecPerKm::from(999.0);
        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSplitNumber(1)
                ->withDistanceInMeter(1000)
                ->withGapPace($existingGap)
                ->build()
        );

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertEqualsWithDelta(
            999.0,
            $metricSplits->toArray()[0]->getGapPaceInSecondsPerKm()->toFloat(),
            0.01,
        );
    }

    public function testProcessSkipsNonRunActivity(): void
    {
        $activityId = ActivityId::fromUnprefixed('ride-1');
        $this->addActivity($activityId, SportType::RIDE);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNull($metricSplits->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessSkipsActivityWithMissingStreams(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-no-streams');
        $this->addActivity($activityId, SportType::RUN);
        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNull($metricSplits->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessSkipsActivityWithPartialStreams(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-partial');
        $this->addActivity($activityId, SportType::RUN);

        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::LAT_LNG)
                ->withData([[50.0, 4.0], [50.001, 4.001]])
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::TIME)
                ->withData([0, 60])
                ->build()
        );
        // Missing ALTITUDE stream.

        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNull($metricSplits->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessUpdatesMetricAndImperialSplits(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-both-units');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityId, [1000.0]);

        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withUnitSystem(UnitSystem::IMPERIAL)
                ->withSplitNumber(1)
                ->withDistanceInMeter(1609.34)
                ->build()
        );

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $imperialSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::IMPERIAL);
        $this->assertNotNull($metricSplits->toArray()[0]->getGapPaceInSecondsPerKm());
        $this->assertNotNull($imperialSplits->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessHandlesMultipleActivitiesInSingleRun(): void
    {
        $activityIdOne = ActivityId::fromUnprefixed('run-batch-1');
        $activityIdTwo = ActivityId::fromUnprefixed('run-batch-2');
        $this->addActivity($activityIdOne, SportType::RUN);
        $this->addActivity($activityIdTwo, SportType::RUN);
        $this->addStreams($activityIdOne, $this->buildHillyTrackPoints());
        $this->addStreams($activityIdTwo, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityIdOne, [1000.0]);
        $this->addMetricSplits($activityIdTwo, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $splitsOne = $this->activitySplitRepository->findBy($activityIdOne, UnitSystem::METRIC);
        $splitsTwo = $this->activitySplitRepository->findBy($activityIdTwo, UnitSystem::METRIC);
        $this->assertNotNull($splitsOne->toArray()[0]->getGapPaceInSecondsPerKm());
        $this->assertNotNull($splitsTwo->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessIsIdempotent(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-idempotent');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);
        $gapAfterFirstRun = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)
            ->toArray()[0]->getGapPaceInSecondsPerKm();

        $this->calculateGap->process($output);
        $gapAfterSecondRun = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)
            ->toArray()[0]->getGapPaceInSecondsPerKm();

        $this->assertNotNull($gapAfterFirstRun);
        $this->assertEqualsWithDelta($gapAfterFirstRun->toFloat(), $gapAfterSecondRun->toFloat(), 0.01);
    }

    public function testProcessFiltersOutNonMovingPoints(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-moving-filter');
        $this->addActivity($activityId, SportType::RUN);

        $latLng = [];
        $altitude = [];
        $time = [];
        $moving = [];
        for ($i = 0; $i < 100; ++$i) {
            $latLng[] = [50.0 + $i * 0.0001, 4.0 + $i * 0.0001];
            $altitude[] = 100.0 + $i * 0.5;
            $time[] = $i * 5;
            $moving[] = 0 === $i % 2;
        }

        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::LAT_LNG)
                ->withData($latLng)
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::ALTITUDE)
                ->withData($altitude)
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::TIME)
                ->withData($time)
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::MOVING)
                ->withData($moving)
                ->build()
        );

        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNotNull($splits->toArray()[0]->getGapPaceInSecondsPerKm());
    }

    public function testProcessAcceptsGradeStreamWhenAvailable(): void
    {
        $withGradeActivityId = ActivityId::fromUnprefixed('run-grade-stream');
        $withoutGradeActivityId = ActivityId::fromUnprefixed('run-no-grade-stream');
        $this->addActivity($withGradeActivityId, SportType::RUN);
        $this->addActivity($withoutGradeActivityId, SportType::RUN);

        $trackPoints = $this->buildFlatTrackPoints();
        $this->addStreams($withGradeActivityId, $trackPoints);
        $this->addStreams($withoutGradeActivityId, $trackPoints);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($withGradeActivityId)
                ->withStreamType(StreamType::GRADE)
                ->withData(array_fill(0, count($trackPoints['time']), 5.0))
                ->build()
        );
        $this->addMetricSplits($withGradeActivityId, [1000.0]);
        $this->addMetricSplits($withoutGradeActivityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $splitWithGrade = $this->activitySplitRepository->findBy($withGradeActivityId, UnitSystem::METRIC)->toArray()[0];
        $splitWithoutGrade = $this->activitySplitRepository->findBy($withoutGradeActivityId, UnitSystem::METRIC)->toArray()[0];
        $this->assertNotNull($splitWithGrade->getGapPaceInSecondsPerKm());
        $this->assertNotNull($splitWithoutGrade->getGapPaceInSecondsPerKm());
    }

    public function testProcessKeepsGradeStreamGapCloseToActualPaceOnNearFlatSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-grade-stream-flat');
        $this->addActivity($activityId, SportType::RUN);

        $trackPoints = $this->buildFlatTrackPoints();
        $this->addStreams($activityId, $trackPoints);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::GRADE)
                ->withData(array_fill(0, count($trackPoints['time']), 5.0))
                ->build()
        );
        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $split = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)->toArray()[0];
        $this->assertNotNull($split->getGapPaceInSecondsPerKm());
        $this->assertLessThan(
            8.0,
            abs($split->getGapPaceInSecondsPerKm()->toFloat() - $split->getPaceInSecPerKm()->toFloat()),
            'Splits with up to 5m net elevation change should keep GAP nearly identical to pace.',
        );
    }

    public function testProcessKeepsTinyDownhillGapNearlyIdenticalToPace(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-grade-stream-tiny-downhill');
        $this->addActivity($activityId, SportType::RUN);

        $trackPoints = $this->buildFlatTrackPoints();
        $this->addStreams($activityId, $trackPoints);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::GRADE)
                ->withData(array_fill(0, count($trackPoints['time']), 5.0))
                ->build()
        );
        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withUnitSystem(UnitSystem::METRIC)
                ->withSplitNumber(1)
                ->withDistanceInMeter(1000.0)
                ->withElevationDifferenceInMeter(-1)
                ->build()
        );

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $split = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)->toArray()[0];
        $this->assertNotNull($split->getGapPaceInSecondsPerKm());
        $this->assertLessThan(
            4.0,
            abs($split->getGapPaceInSecondsPerKm()->toFloat() - $split->getPaceInSecPerKm()->toFloat()),
            'Tiny downhill splits should keep GAP essentially identical to pace.',
        );
    }

    public function testProcessPenalizesSteepDownhillSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-grade-stream-steep-downhill');
        $this->addActivity($activityId, SportType::RUN);

        $trackPoints = $this->buildFlatTrackPoints();
        $this->addStreams($activityId, $trackPoints);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::GRADE)
                ->withData(array_fill(0, count($trackPoints['time']), 5.0))
                ->build()
        );
        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withUnitSystem(UnitSystem::METRIC)
                ->withSplitNumber(1)
                ->withDistanceInMeter(1000.0)
                ->withAverageSpeed(\App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond::from(3.533))
                ->withElevationDifferenceInMeter(-42)
                ->build()
        );

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $split = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)->toArray()[0];
        $this->assertNotNull($split->getGapPaceInSecondsPerKm());
        $this->assertGreaterThan(
            $split->getPaceInSecPerKm()->toFloat(),
            $split->getGapPaceInSecondsPerKm()->toFloat(),
            'Steep downhill splits should yield a slower GAP than actual pace.',
        );
        $this->assertGreaterThan(
            295.0,
            $split->getGapPaceInSecondsPerKm()->toFloat(),
            'Steep downhill GAP should stay in a realistic slower-than-pace range.',
        );
    }

    public function testProcessPenalizesModerateDownhillPartialLikeSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-grade-stream-moderate-downhill');
        $this->addActivity($activityId, SportType::RUN);

        $trackPoints = $this->buildFlatTrackPoints();
        $this->addStreams($activityId, $trackPoints);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::GRADE)
                ->withData(array_fill(0, count($trackPoints['time']), 5.0))
                ->build()
        );
        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withUnitSystem(UnitSystem::METRIC)
                ->withSplitNumber(1)
                ->withDistanceInMeter(702.4)
                ->withAverageSpeed(\App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond::from(3.247))
                ->withElevationDifferenceInMeter(-11)
                ->build()
        );

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $split = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)->toArray()[0];
        $this->assertNotNull($split->getGapPaceInSecondsPerKm());
        $this->assertGreaterThan(
            $split->getPaceInSecPerKm()->toFloat(),
            $split->getGapPaceInSecondsPerKm()->toFloat(),
            'Moderate downhill partial splits should yield a slightly slower GAP than actual pace.',
        );
        $this->assertLessThan(
            325.0,
            $split->getGapPaceInSecondsPerKm()->toFloat(),
            'Moderate downhill partial splits should stay close to the expected slower-than-pace range.',
        );
    }

    public function testProcessCapsModerateUphillBenefit(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-grade-stream-moderate-uphill');
        $this->addActivity($activityId, SportType::RUN);

        $trackPoints = $this->buildFlatTrackPoints();
        $this->addStreams($activityId, $trackPoints);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::GRADE)
                ->withData(array_fill(0, count($trackPoints['time']), -5.0))
                ->build()
        );
        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withUnitSystem(UnitSystem::METRIC)
                ->withSplitNumber(1)
                ->withDistanceInMeter(1000.0)
                ->withAverageSpeed(\App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond::from(2.89))
                ->withElevationDifferenceInMeter(15)
                ->build()
        );

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $split = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)->toArray()[0];
        $this->assertNotNull($split->getGapPaceInSecondsPerKm());
        $this->assertLessThanOrEqual(
            $split->getPaceInSecPerKm()->toFloat(),
            $split->getGapPaceInSecondsPerKm()->toFloat(),
            'Moderate uphill splits should not become slower than actual pace.',
        );
        $this->assertGreaterThan(
            328.0,
            $split->getGapPaceInSecondsPerKm()->toFloat(),
            'Moderate uphill benefit should be capped to a realistic range.',
        );
    }

    public function testProcessWithUphillProducesSlowerGapThanFlat(): void
    {
        $flatActivityId = ActivityId::fromUnprefixed('run-flat');
        $uphillActivityId = ActivityId::fromUnprefixed('run-uphill');

        $this->addActivity($flatActivityId, SportType::RUN);
        $this->addActivity($uphillActivityId, SportType::RUN);

        $this->addStreams($flatActivityId, $this->buildFlatTrackPoints());
        $this->addStreams($uphillActivityId, $this->buildUphillTrackPoints());

        $this->addMetricSplits($flatActivityId, [1000.0]);
        $this->addMetricSplits($uphillActivityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $flatGap = $this->activitySplitRepository->findBy($flatActivityId, UnitSystem::METRIC)
            ->toArray()[0]->getGapPaceInSecondsPerKm();
        $uphillGap = $this->activitySplitRepository->findBy($uphillActivityId, UnitSystem::METRIC)
            ->toArray()[0]->getGapPaceInSecondsPerKm();

        $this->assertNotNull($flatGap);
        $this->assertNotNull($uphillGap);
        $this->assertLessThan(
            $flatGap->toFloat(),
            $uphillGap->toFloat(),
            'GAP for uphill should be faster (lower) than actual pace on flat at same speed',
        );
    }

    public function testProcessHandlesNearZeroDistanceSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-tiny-split');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());

        // A normal split followed by a near-zero distance split.
        // After segments fill the first split, the while loop advances to the
        // second split where $remainingInSplit = 0.000001 <= 0.00001,
        // triggering the early-finalize guard.
        $this->addMetricSplits($activityId, [1000.0, 0.000001]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNotNull($splits->toArray()[0]->getGapPaceInSecondsPerKm());
        $this->assertNull($splits->toArray()[1]->getGapPaceInSecondsPerKm());
    }

    public function testProcessFinalizesPartiallyFilledLastSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-partial-last');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());

        // Track points produce ~2370m of GPS segments.
        // Three 1000m splits = 3000m total, so the last split won't be
        // fully filled by segments but should still receive a GAP value.
        $this->addMetricSplits($activityId, [1000.0, 1000.0, 1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNotNull($splits->toArray()[0]->getGapPaceInSecondsPerKm());
        $this->assertNotNull($splits->toArray()[1]->getGapPaceInSecondsPerKm());
        $this->assertNotNull($splits->toArray()[2]->getGapPaceInSecondsPerKm());
    }

    public function testProcessKeepsGapCloseToActualPaceOnGentleRollingTerrain(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-gentle-rolling');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildGentleRollingTrackPoints());
        $this->addMetricSplits($activityId, [1000.0, 1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)->toArray();

        foreach ($splits as $split) {
            $gapPace = $split->getGapPaceInSecondsPerKm();
            $this->assertNotNull($gapPace);
            $this->assertLessThan(
                60.0,
                abs($gapPace->toFloat() - $split->getPaceInSecPerKm()->toFloat()),
                'GAP should stay reasonably close to actual pace on gentle terrain.',
            );
        }
    }

    public function testProcessOutputsProgress(): void
    {
        $activityId = ActivityId::fromUnprefixed('run-progress');
        $this->addActivity($activityId, SportType::RUN);
        $this->addStreams($activityId, $this->buildHillyTrackPoints());
        $this->addMetricSplits($activityId, [1000.0]);

        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $this->assertStringContainsString('Calculated GAP for 1 activities', (string) $output);
    }

    public function testProcessWithNoActivitiesToProcess(): void
    {
        $output = new SpyOutput();
        $this->calculateGap->process($output);

        $this->assertStringContainsString('Calculated GAP for 0 activities', (string) $output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->calculateGap = $this->getContainer()->get(CalculateGap::class);
        $this->activitySplitRepository = $this->getContainer()->get(ActivitySplitRepository::class);
        $this->activityStreamRepository = $this->getContainer()->get(ActivityStreamRepository::class);
        $this->activityRepository = $this->getContainer()->get(ActivityRepository::class);
    }

    private function addActivity(ActivityId $activityId, SportType $sportType): void
    {
        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSportType($sportType)
                ->build(),
            [],
        ));
    }

    /**
     * @param list<float> $splitDistances
     */
    private function addMetricSplits(ActivityId $activityId, array $splitDistances): void
    {
        foreach ($splitDistances as $index => $distance) {
            $this->activitySplitRepository->add(
                ActivitySplitBuilder::fromDefaults()
                    ->withActivityId($activityId)
                    ->withUnitSystem(UnitSystem::METRIC)
                    ->withSplitNumber($index + 1)
                    ->withDistanceInMeter($distance)
                    ->build()
            );
        }
    }

    /**
     * @param list<array{latLng: array<int, array{float, float}>, altitude: list<float>, time: list<int>}> $trackPoints
     */
    private function addStreams(ActivityId $activityId, array $trackPoints): void
    {
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::LAT_LNG)
                ->withData($trackPoints['latLng'])
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::ALTITUDE)
                ->withData($trackPoints['altitude'])
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::TIME)
                ->withData($trackPoints['time'])
                ->build()
        );
    }

    /**
     * @return array{latLng: list<array{float, float}>, altitude: list<float>, time: list<int>}
     */
    private function buildHillyTrackPoints(): array
    {
        $latLng = [];
        $altitude = [];
        $time = [];

        for ($i = 0; $i < 200; ++$i) {
            $latLng[] = [50.0 + $i * 0.00009, 4.0 + $i * 0.00009];
            $altitude[] = 100.0 + 30.0 * sin($i * 0.05);
            $time[] = $i * 5;
        }

        return ['latLng' => $latLng, 'altitude' => $altitude, 'time' => $time];
    }

    /**
     * @return array{latLng: list<array{float, float}>, altitude: list<float>, time: list<int>}
     */
    private function buildFlatTrackPoints(): array
    {
        $latLng = [];
        $altitude = [];
        $time = [];

        for ($i = 0; $i < 200; ++$i) {
            $latLng[] = [50.0 + $i * 0.00009, 4.0];
            $altitude[] = 100.0;
            $time[] = $i * 5;
        }

        return ['latLng' => $latLng, 'altitude' => $altitude, 'time' => $time];
    }

    /**
     * @return array{latLng: list<array{float, float}>, altitude: list<float>, time: list<int>}
     */
    private function buildUphillTrackPoints(): array
    {
        $latLng = [];
        $altitude = [];
        $time = [];

        for ($i = 0; $i < 200; ++$i) {
            $latLng[] = [50.0 + $i * 0.00009, 4.0];
            $altitude[] = 100.0 + $i * 1.5;
            $time[] = $i * 5;
        }

        return ['latLng' => $latLng, 'altitude' => $altitude, 'time' => $time];
    }

    /**
     * @return array{latLng: list<array{float, float}>, altitude: list<float>, time: list<int>}
     */
    private function buildGentleRollingTrackPoints(): array
    {
        $latLng = [];
        $altitude = [];
        $time = [];

        for ($i = 0; $i < 220; ++$i) {
            $latLng[] = [50.0 + $i * 0.00009, 4.0];
            $altitude[] = 100.0 + 2.5 * sin($i * 0.08);
            $time[] = $i * 6;
        }

        return ['latLng' => $latLng, 'altitude' => $altitude, 'time' => $time];
    }
}
