<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIdFactory;

final class IncrementingActivityIdFactory implements ActivityIdFactory
{
    private int $counter = 0;

    public function random(): ActivityId
    {
        return ActivityId::fromUnprefixed((string) ++$this->counter);
    }
}
