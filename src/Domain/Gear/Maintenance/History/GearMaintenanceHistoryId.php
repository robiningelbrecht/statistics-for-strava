<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\History;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class GearMaintenanceHistoryId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'gearMaintenance-';
    }
}
