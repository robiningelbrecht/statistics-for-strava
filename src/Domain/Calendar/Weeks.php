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
        /** @var SerializableDateTime $start */
        $start = $startDate->modify('monday this week');
        $end = $now->modify('sunday this week');

        $weeks = [];
        while ($start <= $end) {
            $week = Week::fromDate($start);
            $weeks[$week->getId()] = $week;

            $start = $start->modify('+1 week');
        }

        return Weeks::fromArray(array_values($weeks));
    }
}
