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
    case FORTY_FIVE_DAYS = 'streakFortyFiveDays';
    case TWO_MONTHS = 'streakTwoMonths';
    case FULL_QUARTER = 'streakFullQuarter';
    case HUNDRED_DAYS = 'streakHundredDays';
    case FOUR_MONTHS = 'streakFourMonths';
    case FIVE_MONTHS = 'streakFiveMonths';
    case HALF_YEAR = 'streakHalfYear';
    case TWO_HUNDRED_FIFTY_DAYS = 'streakTwoHundredFiftyDays';
    case FULL_YEAR = 'streakFullYear';
    case FIVE_HUNDRED_DAYS = 'streakFiveHundredDays';
    case TWO_YEARS = 'streakTwoYears';

    public static function resolve(int $days): ?self
    {
        return match ($days) {
            7 => self::FULL_WEEK,
            14 => self::FORTNIGHT,
            21 => self::TWENTY_ONE_DAYS_HABIT,
            30 => self::FULL_MONTH,
            45 => self::FORTY_FIVE_DAYS,
            60 => self::TWO_MONTHS,
            90 => self::FULL_QUARTER,
            100 => self::HUNDRED_DAYS,
            120 => self::FOUR_MONTHS,
            150 => self::FIVE_MONTHS,
            180 => self::HALF_YEAR,
            250 => self::TWO_HUNDRED_FIFTY_DAYS,
            365 => self::FULL_YEAR,
            500 => self::FIVE_HUNDRED_DAYS,
            730 => self::TWO_YEARS,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::FULL_WEEK => $translator->trans("That's a full week of daily activity", locale: $locale),
            self::FORTNIGHT => $translator->trans("That's a full fortnight of daily activity", locale: $locale),
            self::TWENTY_ONE_DAYS_HABIT => $translator->trans("That's 3 weeks straight, they say it takes 21 days to build a habit", locale: $locale),
            self::FULL_MONTH => $translator->trans("That's a full month without a rest day", locale: $locale),
            self::FORTY_FIVE_DAYS => $translator->trans("That's 45 days, halfway to a full quarter", locale: $locale),
            self::TWO_MONTHS => $translator->trans("That's 2 months without missing a day", locale: $locale),
            self::FULL_QUARTER => $translator->trans("That's a full quarter of daily activity", locale: $locale),
            self::HUNDRED_DAYS => $translator->trans("That's 100 days of commitment, triple digits!", locale: $locale),
            self::FOUR_MONTHS => $translator->trans("That's 4 months of unbroken daily activity", locale: $locale),
            self::FIVE_MONTHS => $translator->trans("That's 5 months, nearly half a year without a rest day", locale: $locale),
            self::HALF_YEAR => $translator->trans("That's half a year without skipping a single day", locale: $locale),
            self::TWO_HUNDRED_FIFTY_DAYS => $translator->trans("That's 250 days, over two thirds of a year", locale: $locale),
            self::FULL_YEAR => $translator->trans("That's a full year of daily activity", locale: $locale),
            self::FIVE_HUNDRED_DAYS => $translator->trans("That's 500 days, well beyond a year of daily activity", locale: $locale),
            self::TWO_YEARS => $translator->trans("That's 2 full years of daily activity, incredible!", locale: $locale),
        };
    }
}
