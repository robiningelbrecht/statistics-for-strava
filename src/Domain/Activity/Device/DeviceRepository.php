<?php

declare(strict_types=1);

namespace App\Domain\Activity\Device;

interface DeviceRepository
{
    /**
     * @return Device[]
     */
    public function findAll(): array;
}
