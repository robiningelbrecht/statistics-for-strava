<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\Maintenance\HashtagPrefix;
use PHPUnit\Framework\TestCase;

class HashtagPrefixTest extends TestCase
{
    public function testItShouldWork(): void
    {
        $hashtagPrefix = HashtagPrefix::fromString('test-');

        $this->assertEquals(
            'test-',
            (string) $hashtagPrefix
        );
    }

    public function testItShouldThrowWhenStartsWithHashtag(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('HashtagPrefix #test can not start with #'));

        HashtagPrefix::fromString('#test');
    }

    public function testItShouldThrowWhenNotEndsWithHyphen(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('HashtagPrefix test needs to end with -'));

        HashtagPrefix::fromString('test');
    }
}
