<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Activity\Activities;

final readonly class Day
{
    private function __construct(
        private int $dayNumber,
        private bool $isCurrentMonth,
        private Activities $activities,
    ) {
    }

    public static function create(
        int $dayNumber,
        bool $isCurrentMonth,
        Activities $activities,
    ): self {
        return new self(
            dayNumber: $dayNumber,
            isCurrentMonth: $isCurrentMonth,
            activities: $activities
        );
    }

    public function getDayNumber(): int
    {
        return $this->dayNumber;
    }

    public function isCurrentMonth(): bool
    {
        return $this->isCurrentMonth;
    }

    public function getActivities(): Activities
    {
        return $this->activities;
    }
}
