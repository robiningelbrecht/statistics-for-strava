<?php

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Gears;

interface ImportedGearRepository
{
    public function save(ImportedGear $gear): void;

    public function findAll(): Gears;

    public function find(GearId $gearId): ImportedGear;
}
