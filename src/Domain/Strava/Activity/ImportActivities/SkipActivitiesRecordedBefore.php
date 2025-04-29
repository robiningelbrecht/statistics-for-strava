<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class SkipActivitiesRecordedBefore extends SerializableDateTime
{
    public static function fromOptionalString(?string $string): ?self
    {
        if (is_null($string) || empty(trim($string))) {
            return null;
        }

        return new self($string);
    }
}
