<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Collection;

final class ActivityTypes extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityType::class;
    }
}
