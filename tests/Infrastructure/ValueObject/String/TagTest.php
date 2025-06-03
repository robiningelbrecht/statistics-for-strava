<?php

namespace App\Tests\Infrastructure\ValueObject\String;

use App\Domain\Strava\Gear\Maintenance\InvalidGearMaintenanceConfig;
use App\Infrastructure\ValueObject\String\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testItShouldThrowWhenInvalidTag(): void
    {
        $this->expectExceptionObject(new InvalidGearMaintenanceConfig(
            'Invalid component tag "invalid tag", no spaces allowed.'
        ));

        Tag::fromString('invalid tag');
    }
}
