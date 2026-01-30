<?php

declare(strict_types=1);

namespace App\Domain\Activity\Split;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ActivitySplit>
 */
final class ActivitySplits extends Collection
{
    public function getItemClassName(): string
    {
        return ActivitySplit::class;
    }
}
