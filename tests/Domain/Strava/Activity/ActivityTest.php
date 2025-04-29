<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Infrastructure\Serialization\Json;
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
        $activity->enrichWithMaintenanceTags(['#hashtag', '#another-one']);

        $this->assertEquals('Test Activity', $activity->getName());
    }
}
