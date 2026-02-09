<?php

namespace App\Tests\Infrastructure\ValueObject\String;

use App\Infrastructure\ValueObject\String\CompressedString;
use PHPUnit\Framework\TestCase;

class CompressedStringTest extends TestCase
{
    public function testUncompress(): void
    {
        $json = '{
  "activity": {
    "id": 12345,
    "name": "Short Ride",
    "athlete": {
      "id": 987,
      "name": "Robin"
    },
    "streams": {
      "time": [0, 60, 120],
      "distance": [0, 1.2, 2.5],
      "heartrate": [140, 145, 150]
    },
    "gps": [
      {"lat": 51.2001, "lng": 3.2164},
      {"lat": 51.2002, "lng": 3.2165}
    ],
    "tags": ["morning", "endurance"]
  }
}';
        $compressed = CompressedString::fromUncompressed($json);

        $this->assertEquals(
            $json,
            $compressed->uncompress(),
        );
    }

    public function testUncompressThrowsOnInvalidData(): void
    {
        $this->expectExceptionObject(new \RuntimeException('ZSTD decompression failed'));

        $compressed = CompressedString::fromCompressed('this-is-not-zstd');
        $compressed->uncompress();
    }
}
