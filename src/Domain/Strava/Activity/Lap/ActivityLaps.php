<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Lap;

use App\Infrastructure\ValueObject\Collection;

final class ActivityLaps extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityLap::class;
    }
}
