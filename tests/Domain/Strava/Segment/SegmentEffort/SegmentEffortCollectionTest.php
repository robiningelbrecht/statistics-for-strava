<?php

namespace App\Tests\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEfforts;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use PHPUnit\Framework\TestCase;

class SegmentEffortCollectionTest extends TestCase
{
    public function testGetBestEffort(): void
    {
        $bestEffort = SegmentEffortBuilder::fromDefaults()
            ->withElapsedTimeInSeconds(5.3)
            ->withAverageWatts(200)
            ->withDistance(Kilometer::from(0.2))
            ->build();
        $collection = SegmentEfforts::fromArray([
            SegmentEffortBuilder::fromDefaults()
                ->withElapsedTimeInSeconds(9.3)
                ->withAverageWatts(200)
                ->withDistance(Kilometer::from(0.1))
                ->build(),
            $bestEffort,
        ]);

        $this->assertEquals(
            $bestEffort,
            $collection->getBestEffort()
        );
    }
}
