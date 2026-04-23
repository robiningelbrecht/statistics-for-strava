<?php

namespace App\Tests\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Application\Import\CalculateActivityMetrics\Pipeline\CalculateSplitAverageHeartRate;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Split\ActivitySplitBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\SpyOutput;

class CalculateSplitAverageHeartRateTest extends ContainerTestCase
{
    private CalculateSplitAverageHeartRate $calculateSplitAverageHeartRate;
    private ActivitySplitRepository $activitySplitRepository;
    private ActivityStreamRepository $activityStreamRepository;
    private ActivityRepository $activityRepository;

    public function testProcessCalculatesAverageHeartRateForSingleSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('single-split');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, array_fill(0, 110, 150));
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 110);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertSame(150, $splits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessCalculatesCorrectAveragePerSplit(): void
    {
        $activityId = ActivityId::fromUnprefixed('multi-split');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, [
            ...array_fill(0, 60, 120),
            ...array_fill(0, 60, 180),
        ]);
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 60);
        $this->addSplit($activityId, UnitSystem::METRIC, 2, 60);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertSame(120, $splits->toArray()[0]->getAverageHeartRate());
        $this->assertSame(180, $splits->toArray()[1]->getAverageHeartRate());
    }

    public function testProcessRoundsAverageHeartRate(): void
    {
        $activityId = ActivityId::fromUnprefixed('rounding');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, [140, 141, 142]);
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 3);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertSame(141, $splits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessCalculatesForBothUnitSystems(): void
    {
        $activityId = ActivityId::fromUnprefixed('both-units');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, array_fill(0, 200, 150));
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 100);
        $this->addSplit($activityId, UnitSystem::IMPERIAL, 1, 160);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $metricSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $imperialSplits = $this->activitySplitRepository->findBy($activityId, UnitSystem::IMPERIAL);
        $this->assertSame(150, $metricSplits->toArray()[0]->getAverageHeartRate());
        $this->assertSame(150, $imperialSplits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessSkipsActivityAlreadyWithAverageHeartRate(): void
    {
        $activityId = ActivityId::fromUnprefixed('already-done');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, array_fill(0, 110, 160));

        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSplitNumber(1)
                ->withMovingTimeInSeconds(110)
                ->withAverageHeartRate(999)
                ->build()
        );

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertSame(999, $splits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessSkipsActivityWithoutHeartRateStream(): void
    {
        $activityId = ActivityId::fromUnprefixed('no-hr-stream');
        $this->addActivity($activityId);
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 110);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNull($splits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessSkipsActivityWithEmptyHeartRateStream(): void
    {
        $activityId = ActivityId::fromUnprefixed('empty-hr');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, []);
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 110);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertNull($splits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessHandlesHeartRateStreamShorterThanSplitMovingTime(): void
    {
        $activityId = ActivityId::fromUnprefixed('short-hr');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, [140, 150, 160]);
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 110);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertSame(150, $splits->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessSkipsSplitWhenHeartRateDataIsExhausted(): void
    {
        $activityId = ActivityId::fromUnprefixed('exhausted-hr');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, array_fill(0, 60, 150));
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 60);
        $this->addSplit($activityId, UnitSystem::METRIC, 2, 60);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splits = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC);
        $this->assertSame(150, $splits->toArray()[0]->getAverageHeartRate());
        $this->assertNull($splits->toArray()[1]->getAverageHeartRate());
    }

    public function testProcessHandlesMultipleActivities(): void
    {
        $activityIdOne = ActivityId::fromUnprefixed('batch-1');
        $activityIdTwo = ActivityId::fromUnprefixed('batch-2');
        $this->addActivity($activityIdOne);
        $this->addActivity($activityIdTwo);
        $this->addHeartRateStream($activityIdOne, array_fill(0, 60, 130));
        $this->addHeartRateStream($activityIdTwo, array_fill(0, 60, 170));
        $this->addSplit($activityIdOne, UnitSystem::METRIC, 1, 60);
        $this->addSplit($activityIdTwo, UnitSystem::METRIC, 1, 60);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());

        $splitsOne = $this->activitySplitRepository->findBy($activityIdOne, UnitSystem::METRIC);
        $splitsTwo = $this->activitySplitRepository->findBy($activityIdTwo, UnitSystem::METRIC);
        $this->assertSame(130, $splitsOne->toArray()[0]->getAverageHeartRate());
        $this->assertSame(170, $splitsTwo->toArray()[0]->getAverageHeartRate());
    }

    public function testProcessIsIdempotent(): void
    {
        $activityId = ActivityId::fromUnprefixed('idempotent');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, array_fill(0, 60, 155));
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 60);

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());
        $firstRun = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)
            ->toArray()[0]->getAverageHeartRate();

        $this->calculateSplitAverageHeartRate->process(new SpyOutput());
        $secondRun = $this->activitySplitRepository->findBy($activityId, UnitSystem::METRIC)
            ->toArray()[0]->getAverageHeartRate();

        $this->assertSame(155, $firstRun);
        $this->assertSame($firstRun, $secondRun);
    }

    public function testProcessOutputsProgress(): void
    {
        $activityId = ActivityId::fromUnprefixed('progress');
        $this->addActivity($activityId);
        $this->addHeartRateStream($activityId, array_fill(0, 60, 150));
        $this->addSplit($activityId, UnitSystem::METRIC, 1, 60);

        $output = new SpyOutput();
        $this->calculateSplitAverageHeartRate->process($output);

        $this->assertStringContainsString('Calculated split avg heart rate for 1 activities', (string) $output);
    }

    public function testProcessWithNoActivitiesToProcess(): void
    {
        $output = new SpyOutput();
        $this->calculateSplitAverageHeartRate->process($output);

        $this->assertStringContainsString('Calculated split avg heart rate for 0 activities', (string) $output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->calculateSplitAverageHeartRate = $this->getContainer()->get(CalculateSplitAverageHeartRate::class);
        $this->activitySplitRepository = $this->getContainer()->get(ActivitySplitRepository::class);
        $this->activityStreamRepository = $this->getContainer()->get(ActivityStreamRepository::class);
        $this->activityRepository = $this->getContainer()->get(ActivityRepository::class);
    }

    private function addActivity(ActivityId $activityId): void
    {
        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSportType(SportType::RUN)
                ->build(),
            [],
        ));
    }

    /**
     * @param list<int> $heartRateData
     */
    private function addHeartRateStream(ActivityId $activityId, array $heartRateData): void
    {
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withStreamType(StreamType::HEART_RATE)
                ->withData($heartRateData)
                ->build()
        );
    }

    private function addSplit(ActivityId $activityId, UnitSystem $unitSystem, int $splitNumber, int $movingTimeInSeconds): void
    {
        $this->activitySplitRepository->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withUnitSystem($unitSystem)
                ->withSplitNumber($splitNumber)
                ->withMovingTimeInSeconds($movingTimeInSeconds)
                ->build()
        );
    }
}
