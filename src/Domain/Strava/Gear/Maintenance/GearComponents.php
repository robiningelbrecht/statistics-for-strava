<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Infrastructure\ValueObject\Collection;

final class GearComponents extends Collection
{
    public function getItemClassName(): string
    {
        return GearComponent::class;
    }
}
