<?php

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Activity\ActivityIds;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Gears;

interface ImportedGearRepository
{
    public function save(ImportedGear $gear): void;

    public function findAll(): Gears;

    public function findAllUsed(): Gears;

    public function find(GearId $gearId): ImportedGear;

    public function findUniqueStravaGearIds(?ActivityIds $restrictToActivityIds): GearIds;
}
