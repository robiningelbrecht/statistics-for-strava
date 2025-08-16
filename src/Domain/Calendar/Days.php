<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Day>
 */
final class Days extends Collection
{
    public function getItemClassName(): string
    {
        return Day::class;
    }
}
