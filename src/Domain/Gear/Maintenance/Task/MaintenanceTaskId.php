<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class MaintenanceTaskId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'maintenanceTask-';
    }
}
