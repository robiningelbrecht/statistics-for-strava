<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

interface RecordingDeviceRepository
{
    public function findAll(): RecordingDevices;

    public function find(RecordingDeviceId $recordingDeviceId): RecordingDevice;

    public function save(RecordingDevice $recordingDevice): void;
}
