<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum BestEffortPeriod: string implements TranslatableInterface
{
    case ALL_TIME = 'allTime';
    case FOUR_WEEKS = 'fourWeeks';
    case TWELVE_WEEKS = 'twelveWeeks';
    case SIX_MONTHS = 'sixMonths';
    case YEAR = 'year';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::FOUR_WEEKS => $translator->trans('{numberOfWeeks} weeks', ['{numberOfWeeks}' => 4], locale: $locale),
            self::TWELVE_WEEKS => $translator->trans('{numberOfWeeks} weeks', ['{numberOfWeeks}' => 12], locale: $locale),
            self::SIX_MONTHS => $translator->trans('{numberOfMonths} months', ['{numberOfMonths}' => 6], locale: $locale),
            self::YEAR => $translator->trans('YTD', locale: $locale),
            self::ALL_TIME => $translator->trans('All time', locale: $locale),
        };
    }

    public function getDateRange(SerializableDateTime $now): DateRange
    {
        return match ($this) {
            self::FOUR_WEEKS => DateRange::lastXDays($now, 28),
            self::TWELVE_WEEKS => DateRange::lastXDays($now, 84),
            self::SIX_MONTHS => DateRange::fromDates(
                from: $now->sub(\DateInterval::createFromDateString('6 months')),
                till: $now,
            ),
            self::YEAR => DateRange::fromDates(
                from: $now->sub(\DateInterval::createFromDateString('1 year')),
                till: $now,
            ),
            self::ALL_TIME => DateRange::upUntilNow(),
        };
    }
}
