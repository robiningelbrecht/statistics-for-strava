<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log;

interface GearMaintenanceLogRepository
{
    public function add(GearMaintenanceLog $gearMaintenanceLog): void;

    public function update(GearMaintenanceLog $gearMaintenanceLog): void;

    public function find(GearMaintenanceLogId $gearMaintenanceLogId): GearMaintenanceLog;

    public function delete(GearMaintenanceLogId $gearMaintenanceLogId): void;

    public function findAll(): GearMaintenanceLogs;
}
