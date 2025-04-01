<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

interface GearStatsRepository
{
    public function findStatsPerGearIdPerDay(): GearStats;
}
