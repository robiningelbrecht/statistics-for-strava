<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

interface RecordingDeviceRepository
{
    public function findAll(): RecordingDevices;
}
