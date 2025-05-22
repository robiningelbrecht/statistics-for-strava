<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Clock;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;

readonly class SystemClock implements Clock
{
    public function __construct(
        private ?SerializableTimezone $timezone,
    ) {
    }

    public function getCurrentDateTimeImmutable(): SerializableDateTime
    {
        return new SerializableDateTime('now', $this->timezone);
    }
}
