<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<\App\Domain\Rewind\RewindItem>
 */
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
