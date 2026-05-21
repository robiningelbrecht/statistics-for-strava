<?php

namespace App\Tests\Domain\Activity\Stream\Metric;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\DbalActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetrics;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\Metric\DbalActivityStreamMetricRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;

class DbalActivityStreamMetricRepositoryTest extends ContainerTestCase
{
    private ActivityStreamMetricRepository $activityStreamMetricRepository;
    private DbalActivityStreamRepository $activityStreamRepository;

    public function testAdd(): void
    {
        $metric = ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [100, 200, 300],
        );

        $this->activityStreamMetricRepository->add($metric);

        $found = $this->activityStreamMetricRepository->findByActivityIdAndMetricType(
            ActivityId::fromUnprefixed('1'),
            ActivityStreamMetricType::BEST_AVERAGES,
        );

        $this->assertEquals(ActivityStreamMetrics::fromArray([$metric]), $found);
    }

    public function testDeleteForActivity(): void
    {
        $activityId = ActivityId::fromUnprefixed('1');

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: $activityId,
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [100],
        ));
        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: $activityId,
            streamType: StreamType::HEART_RATE,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [150],
        ));

        $otherMetric = ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('2'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [200],
        );
        $this->activityStreamMetricRepository->add($otherMetric);

        $this->activityStreamMetricRepository->deleteForActivity($activityId);

        $this->assertEquals(
            ActivityStreamMetrics::fromArray([]),
            $this->activityStreamMetricRepository->findByActivityIdAndMetricType($activityId, ActivityStreamMetricType::BEST_AVERAGES),
        );
        $this->assertEquals(
            ActivityStreamMetrics::fromArray([$otherMetric]),
            $this->activityStreamMetricRepository->findByActivityIdAndMetricType(ActivityId::fromUnprefixed('2'), ActivityStreamMetricType::BEST_AVERAGES),
        );
    }

    public function testFindActivityIdsWithoutBestAverages(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::WATTS);
        $this->addStream(ActivityId::fromUnprefixed('2'), StreamType::WATTS);
        $this->addStream(ActivityId::fromUnprefixed('3'), StreamType::WATTS);
        // Activity 4 has a stream type that doesn't support best averages.
        $this->addStream(ActivityId::fromUnprefixed('4'), StreamType::TIME);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [100],
        ));

        $result = $this->activityStreamMetricRepository->findActivityIdsWithoutBestAverages();

        $this->assertEquals(
            ActivityIds::fromArray([
                ActivityId::fromUnprefixed('2'),
                ActivityId::fromUnprefixed('3'),
            ]),
            $result,
        );
    }

    public function testFindActivityIdsWithoutBestAveragesWhenAllHaveMetrics(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::WATTS);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [100],
        ));

        $this->assertEquals(
            ActivityIds::fromArray([]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutBestAverages(),
        );
    }

    public function testFindActivityIdsWithoutNormalizedPower(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::WATTS);
        $this->addStream(ActivityId::fromUnprefixed('2'), StreamType::WATTS);
        // Activity 3 has heart rate, not watts - should not appear.
        $this->addStream(ActivityId::fromUnprefixed('3'), StreamType::HEART_RATE);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::NORMALIZED_POWER,
            data: [250],
        ));

        $result = $this->activityStreamMetricRepository->findActivityIdsWithoutNormalizedPower();

        $this->assertEquals(
            ActivityIds::fromArray([ActivityId::fromUnprefixed('2')]),
            $result,
        );
    }

    public function testFindActivityIdsWithoutNormalizedPowerWhenAllHaveMetrics(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::WATTS);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::NORMALIZED_POWER,
            data: [250],
        ));

        $this->assertEquals(
            ActivityIds::fromArray([]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutNormalizedPower(),
        );
    }

    public function testFindActivityIdsWithoutDistributionValues(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::WATTS);
        $this->addStream(ActivityId::fromUnprefixed('2'), StreamType::HEART_RATE);
        $this->addStream(ActivityId::fromUnprefixed('3'), StreamType::VELOCITY);
        // Activity 4 has a stream type that doesn't support distribution.
        $this->addStream(ActivityId::fromUnprefixed('4'), StreamType::ALTITUDE);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::VALUE_DISTRIBUTION,
            data: [10, 20],
        ));

        $result = $this->activityStreamMetricRepository->findActivityIdsWithoutDistributionValues();

        $this->assertEquals(
            ActivityIds::fromArray([
                ActivityId::fromUnprefixed('2'),
                ActivityId::fromUnprefixed('3'),
            ]),
            $result,
        );
    }

    public function testFindActivityIdsWithoutDistributionValuesWhenAllHaveMetrics(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::WATTS);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::VALUE_DISTRIBUTION,
            data: [10, 20],
        ));

        $this->assertEquals(
            ActivityIds::fromArray([]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutDistributionValues(),
        );
    }

    public function testFindActivityIdsWithoutEncodedPolyline(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::LAT_LNG);
        $this->addStream(ActivityId::fromUnprefixed('2'), StreamType::LAT_LNG);
        $this->addStream(ActivityId::fromUnprefixed('3'), StreamType::WATTS);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::LAT_LNG,
            metricType: ActivityStreamMetricType::ENCODED_POLYLINE,
            data: ['encoded'],
        ));

        $result = $this->activityStreamMetricRepository->findActivityIdsWithoutEncodedPolyline();

        $this->assertEquals(
            ActivityIds::fromArray([ActivityId::fromUnprefixed('2')]),
            $result,
        );
    }

    public function testFindActivityIdsWithoutEncodedPolylineWhenAllHaveMetrics(): void
    {
        $this->addStream(ActivityId::fromUnprefixed('1'), StreamType::LAT_LNG);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('1'),
            streamType: StreamType::LAT_LNG,
            metricType: ActivityStreamMetricType::ENCODED_POLYLINE,
            data: ['encoded'],
        ));

        $this->assertEquals(
            ActivityIds::fromArray([]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutEncodedPolyline(),
        );
    }

    public function testFindActivityIdsWithoutAerobicDecoupling(): void
    {
        $eligibleActivityId = ActivityId::fromUnprefixed('1');
        $tooShortActivityId = ActivityId::fromUnprefixed('2');
        $rideActivityId = ActivityId::fromUnprefixed('3');
        $missingStreamActivityId = ActivityId::fromUnprefixed('4');
        $alreadyCalculatedActivityId = ActivityId::fromUnprefixed('5');

        $this->addActivity($eligibleActivityId, SportType::RUN, 1800);
        $this->addRequiredAerobicDecouplingStreams($eligibleActivityId);
        $this->addActivity($tooShortActivityId, SportType::RUN, 1799);
        $this->addRequiredAerobicDecouplingStreams($tooShortActivityId);
        $this->addActivity($rideActivityId, SportType::RIDE, 1800);
        $this->addRequiredAerobicDecouplingStreams($rideActivityId, StreamType::WATTS);
        $this->addActivity($missingStreamActivityId, SportType::TRAIL_RUN, 1800);
        $this->addStream($missingStreamActivityId, StreamType::TIME);
        $this->addActivity($alreadyCalculatedActivityId, SportType::VIRTUAL_RUN, 1800);
        $this->addRequiredAerobicDecouplingStreams($alreadyCalculatedActivityId);

        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: $alreadyCalculatedActivityId,
            streamType: StreamType::VELOCITY,
            metricType: ActivityStreamMetricType::AEROBIC_DECOUPLING,
            data: [4.2],
        ));

        $this->assertEquals(
            ActivityIds::fromArray([$eligibleActivityId, $rideActivityId]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutAerobicDecoupling(1800),
        );
    }

    public function testFindActivityIdsWithoutAerobicDecouplingRequiresRidePowerStream(): void
    {
        $activityId = ActivityId::fromUnprefixed('1');
        $this->addActivity($activityId, SportType::RIDE, 1800);
        $this->addRequiredAerobicDecouplingStreams($activityId);

        $this->assertEquals(
            ActivityIds::fromArray([]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutAerobicDecoupling(1800),
        );
    }

    public function testFindActivityIdsWithoutAerobicDecouplingAllowsZeroMinimumMovingTime(): void
    {
        $activityId = ActivityId::fromUnprefixed('1');
        $this->addActivity($activityId, SportType::RUN, 1);
        $this->addRequiredAerobicDecouplingStreams($activityId);

        $this->assertEquals(
            ActivityIds::fromArray([$activityId]),
            $this->activityStreamMetricRepository->findActivityIdsWithoutAerobicDecoupling(0),
        );
    }

    public function testFindByActivityIdAndMetricType(): void
    {
        $activityId = ActivityId::fromUnprefixed('1');

        $bestAveragesWatts = ActivityStreamMetric::create(
            activityId: $activityId,
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [100, 200],
        );
        $bestAveragesHeartRate = ActivityStreamMetric::create(
            activityId: $activityId,
            streamType: StreamType::HEART_RATE,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [150, 160],
        );
        $normalizedPower = ActivityStreamMetric::create(
            activityId: $activityId,
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::NORMALIZED_POWER,
            data: [250],
        );

        $this->activityStreamMetricRepository->add($bestAveragesWatts);
        $this->activityStreamMetricRepository->add($bestAveragesHeartRate);
        $this->activityStreamMetricRepository->add($normalizedPower);

        // Different activity, should not be included.
        $this->activityStreamMetricRepository->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed('2'),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: [300],
        ));

        $this->assertEquals(
            ActivityStreamMetrics::fromArray([$bestAveragesWatts, $bestAveragesHeartRate]),
            $this->activityStreamMetricRepository->findByActivityIdAndMetricType($activityId, ActivityStreamMetricType::BEST_AVERAGES),
        );

        $this->assertEquals(
            ActivityStreamMetrics::fromArray([$normalizedPower]),
            $this->activityStreamMetricRepository->findByActivityIdAndMetricType($activityId, ActivityStreamMetricType::NORMALIZED_POWER),
        );
    }

    public function testFindByActivityIdAndMetricTypeReturnsEmptyWhenNoneFound(): void
    {
        $this->assertEquals(
            ActivityStreamMetrics::fromArray([]),
            $this->activityStreamMetricRepository->findByActivityIdAndMetricType(
                ActivityId::fromUnprefixed('999'),
                ActivityStreamMetricType::BEST_AVERAGES,
            ),
        );
    }

    private function addStream(ActivityId $activityId, StreamType $streamType): void
    {
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType($streamType)
                ->withData([1])
                ->build()
        );
    }

    private function addRequiredAerobicDecouplingStreams(ActivityId $activityId, StreamType $efficiencyStreamType = StreamType::VELOCITY): void
    {
        $this->addStream($activityId, StreamType::TIME);
        $this->addStream($activityId, StreamType::MOVING);
        $this->addStream($activityId, StreamType::HEART_RATE);
        $this->addStream($activityId, $efficiencyStreamType);
    }

    private function addActivity(ActivityId $activityId, SportType $sportType, int $movingTimeInSeconds): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSportType($sportType)
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->build(),
            []
        ));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityStreamMetricRepository = new DbalActivityStreamMetricRepository(
            $this->getConnection(),
        );
        $this->activityStreamRepository = new DbalActivityStreamRepository(
            $this->getConnection(),
        );
    }
}
