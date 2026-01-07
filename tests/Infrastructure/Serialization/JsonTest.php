<?php

namespace App\Tests\Infrastructure\Serialization;

use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class JsonTest extends TestCase
{
    use MatchesSnapshots;

    public function testEncodeDecode(): void
    {
        $array = ['random' => ['array' => ['with', 'children']]];

        $encoded = Json::encode($array);
        $this->assertMatchesJsonSnapshot($encoded);

        $this->assertEquals($array, Json::decode($encoded));
        $this->assertEquals($array, Json::encodeAndDecode($array));
    }

    public function testDecodeWhenInvalidJson(): void
    {
        $this->expectExceptionObject(new \JsonException('Invalid JSON detected. This is usually caused by corrupted activity data.
Please see the troubleshooting guide for steps to resolve the issue: https://statistics-for-strava-docs.robiningelbrecht.be/#/troubleshooting/import-build-fails for more information.'));

        Json::decode('{"name": "Ride", "distance": 42,}');
    }
}
