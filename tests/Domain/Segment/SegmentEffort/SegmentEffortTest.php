<?php

namespace App\Tests\Domain\Segment\SegmentEffort;

use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use PHPUnit\Framework\TestCase;

class SegmentEffortTest extends TestCase
{
    public function testGetAverageSpeed(): void
    {
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->withElapsedTimeInSeconds(0)
            ->build();

        $this->assertEquals(
            KmPerHour::zero(),
            $segmentEffort->getAverageSpeed()
        );
    }
}
