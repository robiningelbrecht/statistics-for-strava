<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class ActivityId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'activity-';
    }
}
