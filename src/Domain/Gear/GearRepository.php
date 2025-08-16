<?php

declare(strict_types=1);

namespace App\Domain\Gear;

interface GearRepository
{
    public function findAll(): Gears;
}
