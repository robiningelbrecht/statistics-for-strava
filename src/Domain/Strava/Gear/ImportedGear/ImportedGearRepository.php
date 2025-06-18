<?php

namespace App\Domain\Strava\Gear\ImportedGear;

use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\Gears;

interface ImportedGearRepository
{
    public function save(ImportedGear $gear): void;

    public function findAll(): Gears;

    public function find(GearId $gearId): ImportedGear;
}
