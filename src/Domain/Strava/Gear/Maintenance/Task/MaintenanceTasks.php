<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Infrastructure\ValueObject\Collection;

final class MaintenanceTasks extends Collection
{
    public function getItemClassName(): string
    {
        return MaintenanceTask::class;
    }
}
