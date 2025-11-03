<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

use App\Domain\Gear\Gears;

interface CustomGearRepository
{
    public function save(CustomGear $gear): void;

    public function findAll(): Gears;

    public function findAllUsed(): Gears;

    public function removeAll(): void;
}
