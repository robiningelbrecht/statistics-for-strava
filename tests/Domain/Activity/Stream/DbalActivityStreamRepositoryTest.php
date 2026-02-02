<?php

namespace App\Tests\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\DbalActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;

class DbalActivityStreamRepositoryTest extends ContainerTestCase
{
    private ActivityStreamRepository $activityStreamRepository;

    public function testUpdate(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEmpty($stream->getBestAverages());

        $this->activityStreamRepository->update(
            $stream
                ->withBestAverages([1 => 1])
                ->withValueDistribution([2 => 3.2])
        );

        $streams = $this->activityStreamRepository->findByActivityId($stream->getActivityId());
        /** @var \App\Domain\Activity\Stream\ActivityStream $stream */
        $stream = $streams->getFirst();

        $this->assertEquals([1 => 1], $stream->getBestAverages());
        $this->assertEquals([2 => 3.2], $stream->getValueDistribution());
        $this->assertEquals(
            [
                'bestAverages'=> true,
                'valueDistribution'=> true,
            ],
            $stream->getComputedFieldsState()
        );
    }

    public function testHasOneForActivityAndStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: ActivityId::fromUnprefixed(1),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: StreamType::CADENCE
        ));
    }

    public function testFindByStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityStreams::fromArray([$stream]),
            $this->activityStreamRepository->findByStreamType($stream->getStreamType())
        );
    }

    public function testFindActivityIdsByStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($stream);

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::TIME)
            ->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityIds::fromArray([$stream->getActivityId()]),
            $this->activityStreamRepository->findActivityIdsByStreamType($stream->getStreamType())
        );
    }

    public function testFindOneByActivityAndStreamType(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStreamType(StreamType::HEART_RATE)
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            $streamTwo,
            $this->activityStreamRepository->findOneByActivityAndStreamType(
                activityId: ActivityId::fromUnprefixed(1),
                streamType: StreamType::CADENCE
            )
        );
    }

    public function testFindOneByActivityAndStreamTypeItShouldThrow(): void
    {
        $this->expectExceptionObject(new EntityNotFound('ActivityStream activity-1-cadence not found'));

        $this->activityStreamRepository->findOneByActivityAndStreamType(
            activityId: ActivityId::fromUnprefixed(1),
            streamType: StreamType::CADENCE
        );
    }

    public function testFindByActivity(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreams::fromArray([$streamOne, $streamTwo]),
            $this->activityStreamRepository->findByActivityId(
                activityId: ActivityId::fromUnprefixed(1),
            )
        );
    }

    public function testFindWithoutValueDistributions(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::HEART_RATE)
            ->withValueDistribution(['lol'])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            ActivityStreams::fromArray([$streamOne]),
            $this->activityStreamRepository->findWithoutDistributionValues(10)
        );
    }

    public function testFindWithoutBestAverages(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->withBestAverages(['lol'])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            ActivityStreams::fromArray([$streamOne]),
            $this->activityStreamRepository->findWithoutBestAverages(10)
        );
    }

    public function testFindWithoutNormalizedPower(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStreamType(StreamType::WATTS)
            ->withNormalizedPower(3)
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $streamThree = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamThree);

        $this->assertEquals(
            ActivityStreams::fromArray([$streamOne]),
            $this->activityStreamRepository->findWithoutNormalizedPower(10)
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityStreamRepository = new DbalActivityStreamRepository(
            $this->getConnection(),
        );
    }
}
