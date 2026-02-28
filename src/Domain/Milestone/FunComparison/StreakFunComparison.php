<?php

declare(strict_types=1);

namespace App\Domain\Milestone\FunComparison;

use Symfony\Contracts\Translation\TranslatorInterface;

enum StreakFunComparison: string implements FunComparison
{
    case FULL_WEEK = 'streakFullWeek';
    case FORTNIGHT = 'streakFortnight';
    case TWENTY_ONE_DAYS_HABIT = 'streakTwentyOneDaysHabit';
    case FULL_MONTH = 'streakFullMonth';
    case TWO_MONTHS = 'streakTwoMonths';
    case FULL_QUARTER = 'streakFullQuarter';
    case HUNDRED_DAYS = 'streakHundredDays';
    case HALF_YEAR = 'streakHalfYear';
    case FULL_YEAR = 'streakFullYear';

    public static function resolve(int $days): ?self
    {
        return match (true) {
            $days >= 365 => self::FULL_YEAR,
            $days >= 180 => self::HALF_YEAR,
            $days >= 100 => self::HUNDRED_DAYS,
            $days >= 90 => self::FULL_QUARTER,
            $days >= 60 => self::TWO_MONTHS,
            $days >= 30 => self::FULL_MONTH,
            $days >= 21 => self::TWENTY_ONE_DAYS_HABIT,
            $days >= 14 => self::FORTNIGHT,
            $days >= 7 => self::FULL_WEEK,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::FULL_WEEK => $translator->trans("That's a full week without a rest day", locale: $locale),
            self::FORTNIGHT => $translator->trans("That's a full fortnight of daily activity", locale: $locale),
            self::TWENTY_ONE_DAYS_HABIT => $translator->trans("That's 3 weeks straight, they say it takes 21 days to build a habit", locale: $locale),
            self::FULL_MONTH => $translator->trans("That's a full month without a rest day", locale: $locale),
            self::TWO_MONTHS => $translator->trans("That's 2 months without missing a day", locale: $locale),
            self::FULL_QUARTER => $translator->trans("That's a full quarter of daily activity", locale: $locale),
            self::HUNDRED_DAYS => $translator->trans("That's 100 days of commitment, triple digits!", locale: $locale),
            self::HALF_YEAR => $translator->trans("That's half a year without skipping a single day", locale: $locale),
            self::FULL_YEAR => $translator->trans("That's at least one full year of daily activity", locale: $locale),
        };
    }
}
