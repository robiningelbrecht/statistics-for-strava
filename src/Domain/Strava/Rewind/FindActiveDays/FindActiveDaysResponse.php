<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindActiveDays;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindActiveDaysResponse implements Response
{
    public function __construct(
        private int $activeDays,
    ) {
    }

    public function getNumberOfActiveDays(): int
    {
        return $this->activeDays;
    }
}
