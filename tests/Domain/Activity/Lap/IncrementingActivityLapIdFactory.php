<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Lap;

use App\Domain\Activity\Lap\ActivityLapId;
use App\Domain\Activity\Lap\ActivityLapIdFactory;

final class IncrementingActivityLapIdFactory implements ActivityLapIdFactory
{
    private int $counter = 0;

    public function random(): ActivityLapId
    {
        return ActivityLapId::fromUnprefixed((string) ++$this->counter);
    }
}
