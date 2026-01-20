<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ActivityId>
 */
final class ActivityIds extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityId::class;
    }
}
