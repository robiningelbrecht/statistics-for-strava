<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindAvailableRewindOptions;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindAvailableRewindOptions\FindAvailableRewindOptionsResponse>
 */
final readonly class FindAvailableRewindOptions implements Query
{
    public const string ALL_TIME = 'all-time';

    public function __construct(
        private SerializableDateTime $now,
    ) {
    }

    public function getNow(): SerializableDateTime
    {
        return $this->now;
    }
}
