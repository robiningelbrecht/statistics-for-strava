<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindStreaks;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindStreaksResponse implements Response
{
    public function __construct(
        private int $longestDayStreak,
        private int $currentDayStreak,
        private int $longestWeekStreak,
        private int $currentWeekStreak,
        private int $longestMonthStreak,
        private int $currentMonthStreak,
    ) {
    }

    public function getCurrentDayStreak(): int
    {
        return $this->currentDayStreak;
    }

    public function getLongestDayStreak(): int
    {
        return $this->longestDayStreak;
    }

    public function getCurrentWeekStreak(): int
    {
        return $this->currentWeekStreak;
    }

    public function getLongestWeekStreak(): int
    {
        return $this->longestWeekStreak;
    }

    public function getLongestMonthStreak(): int
    {
        return $this->longestMonthStreak;
    }

    public function getCurrentMonthStreak(): int
    {
        return $this->currentMonthStreak;
    }
}
