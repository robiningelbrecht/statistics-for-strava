<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\WorldType;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityTest extends TestCase
{
    use MatchesSnapshots;

    public function testDelete(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $activity->delete();

        $this->assertMatchesJsonSnapshot(Json::encode($activity->getRecordedEvents()));
    }

    public function testGetName(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withName('Test Activity #hashtag')
            ->build();

        $this->assertEquals('Test Activity #hashtag', $activity->getName());

        $activity = ActivityBuilder::fromDefaults()
            ->withName('Test Activity #hashtag #another-one')
            ->build();
        $activity->enrichWithTags(['#hashtag', '#another-one']);

        $this->assertEquals('Test Activity', $activity->getName());
    }

    public function testLeafletMapWithoutStartingCoordinate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withPolyline('line')
            ->withWorldType(WorldType::ZWIFT)
            ->build();

        $this->assertNull($activity->getLeafletMap());
    }

    public function testLeafletMapWhenZwiftMapCouldNotBeDetermined(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withWorldType(WorldType::ZWIFT)
            ->withStartingCoordinate(
                Coordinate::createFromLatAndLng(Latitude::fromString('1'), Longitude::fromString('1'))
            )
            ->withPolyline('line')
            ->build();

        $this->assertNull($activity->getLeafletMap());
    }
}
