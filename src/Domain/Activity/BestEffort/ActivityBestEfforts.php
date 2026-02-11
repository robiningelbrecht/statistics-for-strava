<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ActivityBestEffort>
 */
final class ActivityBestEfforts extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityBestEffort::class;
    }
}
