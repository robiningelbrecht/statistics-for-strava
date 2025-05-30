<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\CustomGear;

use App\Domain\Strava\Gear\Gears;

interface CustomGearRepository
{
    public function save(CustomGear $gear): void;

    public function findAll(): Gears;

    public function removeAll(): void;
}
