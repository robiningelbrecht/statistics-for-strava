<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<GearId>
 */
final class GearIds extends Collection
{
    public function getItemClassName(): string
    {
        return GearId::class;
    }
}
