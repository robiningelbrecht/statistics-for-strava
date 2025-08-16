<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindStreaks;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindStreaksResponse implements Response
{
    public function __construct(
        private int $dayStreak,
        private int $weekStreak,
        private int $monthStreak,
    ) {
    }

    public function getDayStreak(): int
    {
        return $this->dayStreak;
    }

    public function getWeekStreak(): int
    {
        return $this->weekStreak;
    }

    public function getMonthStreak(): int
    {
        return $this->monthStreak;
    }
}
