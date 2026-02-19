<?php

namespace App\Tests\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamBasedActivityPowerRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class StreamBasedActivityPowerRepositoryTest extends ContainerTestCase
{
    private StreamBasedActivityPowerRepository $streamBasedActivityPowerRepository;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testFindBestWhenAthleteWeightNotFound(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2015-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));

        $this->getContainer()->get(ActivityStreamMetricRepository::class)->add(ActivityStreamMetric::create(
            activityId: $activityOne->getId(),
            streamType: StreamType::WATTS,
            metricType: ActivityStreamMetricType::BEST_AVERAGES,
            data: Json::decode('{"1":540,"5":493,"10":460,"15":442,"30":412,"45":373,"60":352,"120":293,"180":273,"240":282,"300":273,"390":262,"480":256,"720":246,"960":239,"1200":239,"1800":225,"2400":222,"3000":222,"3600":218}'),
        ));

        $this->expectException(EntityNotFound::class);
        $this->streamBasedActivityPowerRepository->findBest($activityOne->getId());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->streamBasedActivityPowerRepository = $this->getContainer()->get(StreamBasedActivityPowerRepository::class);
        $this->activityWithRawDataRepository = $this->getContainer()->get(ActivityWithRawDataRepository::class);
    }
}
