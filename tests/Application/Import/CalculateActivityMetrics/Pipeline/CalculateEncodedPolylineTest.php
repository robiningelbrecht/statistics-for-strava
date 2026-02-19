<?php

namespace App\Tests\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Application\Import\CalculateActivityMetrics\Pipeline\CalculateEncodedPolylines;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetric;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricRepository;
use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\CompressedString;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class CalculateEncodedPolylineTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CalculateEncodedPolylines $calculateEncodedPolyline;

    public function testProcess(): void
    {
        $output = new SpyOutput();

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(4))
            ->withStreamType(StreamType::LAT_LNG)
            ->withData([
                [-11.63577, 166.97262],
                [-11.63497, 166.97442],
                [-11.63257, 166.97702],
            ])
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::LAT_LNG)
            ->withData([])
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(5))
            ->withStreamType(StreamType::LAT_LNG)
            ->withData([])
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $this->getContainer()->get(ActivityStreamMetricRepository::class)->add(ActivityStreamMetric::create(
            activityId: ActivityId::fromUnprefixed(5),
            streamType: StreamType::LAT_LNG,
            metricType: ActivityStreamMetricType::ENCODED_POLYLINE,
            data: ['existing'],
        ));

        $this->calculateEncodedPolyline->process($output);

        $this->assertMatchesTextSnapshot($output);
        $this->assertDatabaseResults();
    }

    private function assertDatabaseResults(): void
    {
        $results = $this->getConnection()
            ->executeQuery('SELECT activityId, streamType, metricType, data FROM ActivityStreamMetric WHERE metricType = :metricType', [
                'metricType' => ActivityStreamMetricType::ENCODED_POLYLINE->value,
            ])->fetchAllAssociative();

        foreach ($results as &$result) {
            $result['data'] = CompressedString::fromCompressed($result['data'])->uncompress();
        }

        $this->assertMatchesJsonSnapshot(
            Json::encode($results)
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->calculateEncodedPolyline = $this->getContainer()->get(CalculateEncodedPolylines::class);
    }
}
