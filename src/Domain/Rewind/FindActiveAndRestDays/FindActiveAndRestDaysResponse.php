<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindActiveAndRestDays;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindActiveAndRestDaysResponse implements Response
{
    public function __construct(
        private int $totalNumberOfDays,
        private int $activeDays,
    ) {
    }

    public function getTotalNumberOfDays(): int
    {
        return $this->totalNumberOfDays;
    }

    public function getNumberOfActiveDays(): int
    {
        return $this->activeDays;
    }

    public function getNumberOfRestDays(): int
    {
        return $this->totalNumberOfDays - $this->activeDays;
    }
}
