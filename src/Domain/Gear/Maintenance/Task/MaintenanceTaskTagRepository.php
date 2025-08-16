<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

interface MaintenanceTaskTagRepository
{
    public function findAll(): MaintenanceTaskTags;
}
