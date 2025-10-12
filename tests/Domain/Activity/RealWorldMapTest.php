<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\RealWorldMap;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RealWorldMapTest extends TestCase
{
    use MatchesSnapshots;

    public function testGetBounds(): void
    {
        $this->assertEquals(
            [],
            new RealWorldMap()->getBounds()
        );
    }

    public function testGetTileLayer(): void
    {
        $this->assertEquals(
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            new RealWorldMap()->getTileLayer()
        );
    }

    public function testGetMinAndMaxZoom(): void
    {
        $this->assertEquals(
            17,
            new RealWorldMap()->getMaxZoom()
        );
        $this->assertEquals(
            1,
            new RealWorldMap()->getMinZoom()
        );
    }

    public function testGetOverlayImageUrl(): void
    {
        $this->assertNull(new RealWorldMap()->getOverlayImageUrl());
    }
}
