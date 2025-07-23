<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Infrastructure\ValueObject\Collection;

final class RewindItems extends Collection
{
    public function getItemClassName(): string
    {
        return RewindItem::class;
    }

    public function get(int $index): RewindItem
    {
        return $this->toArray()[$index];
    }
}
