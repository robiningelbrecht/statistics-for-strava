<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log;

use App\Infrastructure\Repository\Overview;
use App\Infrastructure\Repository\Pagination;

interface GearMaintenanceLogOverviewRepository
{
    /**
     * @return Overview<GearMaintenanceLogOverviewItem>
     */
    public function find(
        Pagination $pagination,
    ): Overview;

    public function findOneByGearMaintenanceLogId(GearMaintenanceLogId $gearMaintenanceLogId): GearMaintenanceLogOverviewItem;
}
