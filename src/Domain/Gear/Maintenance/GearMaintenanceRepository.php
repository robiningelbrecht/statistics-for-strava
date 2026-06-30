<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;

interface GearMaintenanceRepository
{
    public function find(): GearMaintenanceConfig;

    public function findMaintenanceTask(MaintenanceTaskId $maintenanceTaskId): ?MaintenanceTask;

    public function findComponentForMaintenanceTask(MaintenanceTaskId $maintenanceTaskId): ?GearComponent;

    public function findComponent(GearComponentId $gearComponentId): ?GearComponent;

    public function updateConfig(bool $isFeatureEnabled, bool $ignoreRetiredGear): void;

    public function saveComponent(GearComponent $gearComponent): void;

    public function deleteComponent(GearComponentId $gearComponentId): void;
}
