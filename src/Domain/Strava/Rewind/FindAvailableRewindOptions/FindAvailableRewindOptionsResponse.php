<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindAvailableRewindOptions;

use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class FindAvailableRewindOptionsResponse implements Response
{
    public function __construct(
        private Years $availableRewindYears,
    ) {
    }

    public function getAvailableOptions(): Years
    {
        return $this->availableRewindYears;
    }
}
