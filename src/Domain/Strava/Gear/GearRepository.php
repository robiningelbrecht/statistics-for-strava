<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

interface GearRepository
{
    public function findAll(): Gears;
}
