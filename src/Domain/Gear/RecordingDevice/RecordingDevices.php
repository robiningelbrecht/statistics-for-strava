<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<RecordingDevice>
 */
class RecordingDevices extends Collection
{
    public function getItemClassName(): string
    {
        return RecordingDevice::class;
    }
}
