<?php

declare(strict_types=1);

namespace App\Application\Import\ImportActivities;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class SkipActivitiesRecordedBefore extends SerializableDateTime
{
    public static function fromOptionalString(?string $string): ?self
    {
        if (is_null($string) || in_array(trim($string), ['', '0'], true)) {
            return null;
        }

        return new self($string);
    }
}
