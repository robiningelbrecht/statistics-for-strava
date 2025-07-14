<?php

namespace App\Tests\Domain\Strava\Segment;

use PHPUnit\Framework\TestCase;

class SegmentTest extends TestCase
{
    public function testGetPolylineReturnsNullByDefault(): void
    {
        $segment = SegmentBuilder::fromDefaults()->build();

        $this->assertNull($segment->getPolyline());
    }

    public function testGetPolylineReturnsSetValue(): void
    {
        $polyline = 'encodedPolyline123';
        $segment = SegmentBuilder::fromDefaults()
            ->withPolyline($polyline)
            ->build();

        $this->assertEquals($polyline, $segment->getPolyline());
    }

    public function testUpdatePolylineUpdatesValue(): void
    {
        $segment = SegmentBuilder::fromDefaults()->build();
        $polyline = 'newEncodedPolyline456';

        $result = $segment->updatePolyline($polyline);

        $this->assertSame($segment, $result);
        $this->assertEquals($polyline, $segment->getPolyline());
    }

    public function testUpdatePolylineCanSetToNull(): void
    {
        $segment = SegmentBuilder::fromDefaults()
            ->withPolyline('existingPolyline')
            ->build();

        $segment->updatePolyline(null);

        $this->assertNull($segment->getPolyline());
    }
}