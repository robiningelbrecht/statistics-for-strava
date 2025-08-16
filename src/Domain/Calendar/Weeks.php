<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @extends Collection<Week>
 */
final class Weeks extends Collection
{
    public function getItemClassName(): string
    {
        return Week::class;
    }

    public static function create(
        SerializableDateTime $startDate,
        SerializableDateTime $now,
    ): self {
        $weeks = [];
        for ($year = $startDate->getYear(); $year <= $now->getYear(); ++$year) {
            $weekNumberStart = $year === $startDate->getYear() ? $startDate->getWeekNumber() : 1;
            $weekNumberStop = $year === $now->getYear() ? $now->getWeekNumber() : 52;
            for ($weekNumber = $weekNumberStart; $weekNumber <= $weekNumberStop; ++$weekNumber) {
                $week = Week::fromYearAndWeekNumber($year, $weekNumber);
                $weeks[$week->getId()] = $week;
            }
        }

        return Weeks::fromArray(array_values($weeks));
    }
}
