<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

use App\Infrastructure\ValueObject\Collection;

final class HeartRateZones extends Collection
{
    public function getItemClassName(): string
    {
        return HeartRateZone::class;
    }
}
