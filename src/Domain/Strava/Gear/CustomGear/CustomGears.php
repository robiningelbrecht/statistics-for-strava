<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\CustomGear;

use App\Domain\Strava\Gear\Gear;
use App\Infrastructure\ValueObject\Collection;

final class CustomGears extends Collection
{
    public function getItemClassName(): string
    {
        return Gear::class;
    }
}
