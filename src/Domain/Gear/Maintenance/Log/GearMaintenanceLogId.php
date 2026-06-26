<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class GearMaintenanceLogId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'gearMaintenance-';
    }
}
