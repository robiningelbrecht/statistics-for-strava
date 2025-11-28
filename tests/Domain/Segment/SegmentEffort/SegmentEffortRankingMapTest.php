<?php

namespace App\Tests\Domain\Segment\SegmentEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Segment\SegmentEffort\SegmentEffortRankingMap;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentId;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;

class SegmentEffortRankingMapTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private SegmentEffortRankingMap $segmentEffortRankingMap;

    #[DataProvider(methodName: 'provideRankings')]
    public function testGetRankFor(?int $expectedRank, SegmentEffortId $segmentEffortId): void
    {
        $this->assertEquals(
            $expectedRank,
            $this->segmentEffortRankingMap->getRankFor($segmentEffortId)
        );
    }

    public static function provideRankings(): array
    {
        return [
            [null, SegmentEffortId::fromUnprefixed('unknown')],
            [1, SegmentEffortId::fromUnprefixed(2)],
            [2, SegmentEffortId::fromUnprefixed(1)],
            [3, SegmentEffortId::fromUnprefixed(3)],
            [1, SegmentEffortId::fromUnprefixed(4)],
        ];
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentEffortRankingMap = new SegmentEffortRankingMap(
            $this->getConnection()
        );

        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1))
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(1))
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withElapsedTimeInSeconds(10)
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1))
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(2))
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withElapsedTimeInSeconds(8)
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1))
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(3))
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withElapsedTimeInSeconds(100)
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(2))
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(4))
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withElapsedTimeInSeconds(10)
                ->build()
        );
    }
}
