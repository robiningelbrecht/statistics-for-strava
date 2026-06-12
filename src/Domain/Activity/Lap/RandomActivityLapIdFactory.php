<?php

declare(strict_types=1);

namespace App\Domain\Activity\Lap;

final readonly class RandomActivityLapIdFactory implements ActivityLapIdFactory
{
    public function random(): ActivityLapId
    {
        return ActivityLapId::random();
    }
}
