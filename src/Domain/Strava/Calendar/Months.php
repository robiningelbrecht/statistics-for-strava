<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @extends Collection<Month>
 */
final class Months extends Collection
{
    public function getItemClassName(): string
    {
        return Month::class;
    }

    public static function create(
        SerializableDateTime $startDate,
        SerializableDateTime $endDate,
    ): self {
        $months = Months::empty();
        $period = new \DatePeriod(
            $startDate->modify('first day of this month'),
            new \DateInterval('P1M'),
            $endDate->modify('last day of this month')
        );

        foreach ($period as $date) {
            $months->add(Month::fromDate(SerializableDateTime::fromDateTimeImmutable($date)));
        }

        return $months;
    }
}
