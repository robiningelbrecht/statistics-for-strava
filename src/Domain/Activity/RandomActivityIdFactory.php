<?php

declare(strict_types=1);

namespace App\Domain\Activity;

final readonly class RandomActivityIdFactory implements ActivityIdFactory
{
    public function random(): ActivityId
    {
        return ActivityId::random();
    }
}
