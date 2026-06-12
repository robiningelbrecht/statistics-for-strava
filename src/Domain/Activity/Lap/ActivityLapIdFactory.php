<?php

declare(strict_types=1);

namespace App\Domain\Activity\Lap;

interface ActivityLapIdFactory
{
    public function random(): ActivityLapId;
}
