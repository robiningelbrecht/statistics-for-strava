<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\Maintenance\InvalidGearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Tag;
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
