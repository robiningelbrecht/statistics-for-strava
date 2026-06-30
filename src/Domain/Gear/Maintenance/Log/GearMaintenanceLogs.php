<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<GearMaintenanceLog>
 */
final class GearMaintenanceLogs extends Collection
{
    public function getItemClassName(): string
    {
        return GearMaintenanceLog::class;
    }
}
