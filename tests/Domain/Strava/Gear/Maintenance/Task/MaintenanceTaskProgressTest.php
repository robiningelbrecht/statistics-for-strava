<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgress;
use PHPUnit\Framework\TestCase;

class MaintenanceTaskProgressTest extends TestCase
{
    public function testItShouldThrowWhenLowerThanZero(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Percentage must be between 0 and 100'));

        MaintenanceTaskProgress::from(-1, 'description');
    }

    public function testItShouldThrowWhenHigherThanHundrerd(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Percentage must be between 0 and 100'));

        MaintenanceTaskProgress::from(101, 'description');
    }
}
