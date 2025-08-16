<?php

declare(strict_types=1);

namespace App\Domain\Activity\Lap;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class ActivityLapId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'activityLap-';
    }
}
