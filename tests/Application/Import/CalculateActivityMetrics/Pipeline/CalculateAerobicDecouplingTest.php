<?php

declare(strict_types=1);

namespace App\Tests\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Application\Import\CalculateActivityMetrics\Pipeline\AerobicDecouplingCalculator;
use App\Application\Import\CalculateActivityMetrics\Pipeline\CalculateAerobicDecoupling;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\SpyOutput;

final class CalculateAerobicDecouplingTest extends ContainerTestCase
{
    private ActivityRepository $activityRepository;
    private ActivityStreamRepository $activityStreamRepository;
    private ActivityStreamMetricRepository $activityStreamMetricRepository;

    public function testProcessCalculatesAerobicDecouplingAndNullMarkers(): void
    {
        $validActivityId = ActivityId::fromUnprefixed('1');
        $invalidActivityId = ActivityId::fromUnprefixed('2');

        $this->addActivity($validActivityId);
        $this->addStreams(
            activityId: $validActivityId,
            heartRateData: [100, 100, 100, 100, 100, 100, 110, 110, 110, 110, 110],
        );
        $this->addActivity($invalidActivityId);
        $this->addStreams(
            activityId: $invalidActivityId,
            heartRateData: array_fill(0, 11, 0),
        );

        new CalculateAerobicDecoupling(
            activityStreamRepository: $this->activityStreamRepository,
            activityStreamMetricRepository: $this->activityStreamMetricRepository,
            activityRepository: $this->activityRepository,
            aerobicDecouplingCalculator: new AerobicDecouplingCalculator(),
            minimumMovingTimeInMinutes: 0,
        )->process(new SpyOutput());

        $validMetric = $this->activityStreamMetricRepository
            ->findByActivityIdAndMetricType($validActivityId, ActivityStreamMetricType::AEROBIC_DECOUPLING)
            ->filterOnStreamType(StreamType::VELOCITY);
        $invalidMetric = $this->activityStreamMetricRepository
            ->findByActivityIdAndMetricType($invalidActivityId, ActivityStreamMetricType::AEROBIC_DECOUPLING)
            ->filterOnStreamType(StreamType::VELOCITY);

        $this->assertSame(9.0909, $validMetric->getData()[0]);
        $this->assertNull($invalidMetric->getData()[0]);
    }

    public function testItRejectsNegativeMinimumMovingTime(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException(
            'config/app/config.yaml metrics.aerobicDecoupling.minimumMovingTimeInMinutes must be 0 or greater, got -1'
        ));

        new CalculateAerobicDecoupling(
            activityStreamRepository: $this->activityStreamRepository,
            activityStreamMetricRepository: $this->activityStreamMetricRepository,
            activityRepository: $this->activityRepository,
            aerobicDecouplingCalculator: new AerobicDecouplingCalculator(),
            minimumMovingTimeInMinutes: -1,
        );
    }

    public function testProcessCalculatesRideAerobicDecouplingFromPower(): void
    {
        $activityId = ActivityId::fromUnprefixed('1');
        $this->addActivity($activityId, SportType::RIDE, 60);
        $this->addStream($activityId, StreamType::TIME, range(0, 60));
        $this->addStream($activityId, StreamType::MOVING, array_fill(0, 61, true));
        $this->addStream($activityId, StreamType::HEART_RATE, array_merge(array_fill(0, 31, 100), array_fill(0, 30, 110)));
        $this->addStream($activityId, StreamType::WATTS, array_fill(0, 61, 200));

        new CalculateAerobicDecoupling(
            activityStreamRepository: $this->activityStreamRepository,
            activityStreamMetricRepository: $this->activityStreamMetricRepository,
            activityRepository: $this->activityRepository,
            aerobicDecouplingCalculator: new AerobicDecouplingCalculator(),
            minimumMovingTimeInMinutes: 0,
        )->process(new SpyOutput());

        $metric = $this->activityStreamMetricRepository
            ->findByActivityIdAndMetricType($activityId, ActivityStreamMetricType::AEROBIC_DECOUPLING)
            ->filterOnStreamType(StreamType::WATTS);

        $this->assertSame(9.0909, $metric->getData()[0]);
    }

    private function addActivity(ActivityId $activityId, SportType $sportType = SportType::RUN, int $movingTimeInSeconds = 10): void
    {
        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSportType($sportType)
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->build(),
            []
        ));
    }

    /**
     * @param array<int, int> $heartRateData
     */
    private function addStreams(ActivityId $activityId, array $heartRateData): void
    {
        $this->addStream($activityId, StreamType::TIME, range(0, 10));
        $this->addStream($activityId, StreamType::MOVING, array_fill(0, 11, true));
        $this->addStream($activityId, StreamType::HEART_RATE, $heartRateData);
        $this->addStream($activityId, StreamType::VELOCITY, array_fill(0, 11, 3));
    }

    /**
     * @param array<int, mixed> $data
     */
    private function addStream(ActivityId $activityId, StreamType $streamType, array $data): void
    {
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType($streamType)
                ->withData($data)
                ->build()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityRepository = $this->getContainer()->get(ActivityRepository::class);
        $this->activityStreamRepository = $this->getContainer()->get(ActivityStreamRepository::class);
        $this->activityStreamMetricRepository = $this->getContainer()->get(ActivityStreamMetricRepository::class);
    }
}
