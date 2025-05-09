<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Training\FindNumberOfRestDays;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindNumberOfRestDaysResponse implements Response
{
    public function __construct(
        private int $numberOfRestDays,
    ) {
    }

    public function getNumberOfRestDays(): int
    {
        return $this->numberOfRestDays;
    }
}
