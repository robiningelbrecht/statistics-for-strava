<?php

declare(strict_types=1);

namespace App\Domain\Activity\Device;

use App\Infrastructure\Repository\DbalRepository;

final readonly class DbalDeviceRepository extends DbalRepository implements DeviceRepository
{
    public function findAll(): array
    {
        $results = $this->connection
            ->executeQuery('SELECT deviceName FROM activity 
                  WHERE deviceName IS NOT NULL
                  GROUP BY deviceName
                  ORDER BY COUNT(deviceName) DESC')
            ->fetchFirstColumn();

        return array_map(Device::create(...), $results);
    }
}
