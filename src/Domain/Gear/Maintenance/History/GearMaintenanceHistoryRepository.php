<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\History;

interface GearMaintenanceHistoryRepository
{
    public function add(GearMaintenanceHistory $gearMaintenanceHistory): void;

    public function findAll(): GearMaintenanceHistories;
}
