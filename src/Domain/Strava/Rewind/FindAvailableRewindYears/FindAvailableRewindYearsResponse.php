<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindAvailableRewindYears;

use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class FindAvailableRewindYearsResponse implements Response
{
    public function __construct(
        private Years $availableRewindYears,
    ) {
    }

    public function getAvailableRewindYears(): Years
    {
        return $this->availableRewindYears;
    }
}
