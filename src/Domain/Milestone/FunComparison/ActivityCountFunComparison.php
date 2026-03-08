<?php

declare(strict_types=1);

namespace App\Domain\Milestone\FunComparison;

use Symfony\Contracts\Translation\TranslatorInterface;

enum ActivityCountFunComparison: string implements FunComparison
{
    case ONE_PER_WEEK_TWO_MONTHS = 'countOnePerWeekTwoMonths';
    case ONE_PER_WEEK_HALF_YEAR = 'countOnePerWeekHalfYear';
    case WEEKLY_FOR_YEAR = 'countWeeklyForYear';
    case TWICE_WEEKLY_FOR_YEAR = 'countTwiceWeeklyForYear';
    case FIVE_WEEKLY_FOR_YEAR = 'countFiveWeeklyForYear';
    case TWICE_WEEKLY_FOR_FIVE_YEARS = 'countTwiceWeeklyForFiveYears';
    case THREE_WEEKLY_FOR_FIVE_YEARS = 'countThreeWeeklyForFiveYears';
    case THREE_WEEKLY_FOR_SEVEN_YEARS = 'countThreeWeeklyForSevenYears';
    case FOUR_WEEKLY_FOR_SEVEN_YEARS = 'countFourWeeklyForSevenYears';
    case FIVE_WEEKLY_FOR_SEVEN_YEARS = 'countFiveWeeklyForSevenYears';
    case DAILY_FOR_SEVEN_YEARS = 'countDailyForSevenYears';
    case DAILY_FOR_EIGHT_YEARS = 'countDailyForEightYears';
    case DAILY_FOR_ELEVEN_YEARS = 'countDailyForElevenYears';
    case DAILY_FOR_FOURTEEN_YEARS = 'countDailyForFourteenYears';
    case DAILY_FOR_TWENTY_YEARS = 'countDailyForTwentyYears';
    case DAILY_FOR_TWENTYSEVEN_YEARS = 'countDailyForTwentysevenYears';

    public static function resolve(int $count): ?self
    {
        return match (true) {
            $count >= 10_000 => self::DAILY_FOR_TWENTYSEVEN_YEARS,
            $count >= 7_500 => self::DAILY_FOR_TWENTY_YEARS,
            $count >= 5_000 => self::DAILY_FOR_FOURTEEN_YEARS,
            $count >= 4_000 => self::DAILY_FOR_ELEVEN_YEARS,
            $count >= 3_000 => self::DAILY_FOR_EIGHT_YEARS,
            $count >= 2_500 => self::DAILY_FOR_SEVEN_YEARS,
            $count >= 2_000 => self::FIVE_WEEKLY_FOR_SEVEN_YEARS,
            $count >= 1_500 => self::FOUR_WEEKLY_FOR_SEVEN_YEARS,
            $count >= 1_000 => self::THREE_WEEKLY_FOR_SEVEN_YEARS,
            $count >= 750 => self::THREE_WEEKLY_FOR_FIVE_YEARS,
            $count >= 500 => self::TWICE_WEEKLY_FOR_FIVE_YEARS,
            $count >= 250 => self::FIVE_WEEKLY_FOR_YEAR,
            $count >= 100 => self::TWICE_WEEKLY_FOR_YEAR,
            $count >= 50 => self::WEEKLY_FOR_YEAR,
            $count >= 25 => self::ONE_PER_WEEK_HALF_YEAR,
            $count >= 10 => self::ONE_PER_WEEK_TWO_MONTHS,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::ONE_PER_WEEK_TWO_MONTHS => $translator->trans("That's about one activity per week for two months", locale: $locale),
            self::ONE_PER_WEEK_HALF_YEAR => $translator->trans("That's about one activity per week for half a year", locale: $locale),
            self::WEEKLY_FOR_YEAR => $translator->trans("That's about one activity per week for a year", locale: $locale),
            self::TWICE_WEEKLY_FOR_YEAR => $translator->trans("That's almost 2 activities per week for a year", locale: $locale),
            self::FIVE_WEEKLY_FOR_YEAR => $translator->trans("That's nearly 5 activities per week for a year", locale: $locale),
            self::TWICE_WEEKLY_FOR_FIVE_YEARS => $translator->trans("That's 2 activities per week for nearly 5 years", locale: $locale),
            self::THREE_WEEKLY_FOR_FIVE_YEARS => $translator->trans("That's 3 activities per week for nearly 5 years", locale: $locale),
            self::THREE_WEEKLY_FOR_SEVEN_YEARS => $translator->trans("That's almost 3 activities per week for 7 years", locale: $locale),
            self::FOUR_WEEKLY_FOR_SEVEN_YEARS => $translator->trans("That's nearly 4 activities per week for 7 years", locale: $locale),
            self::FIVE_WEEKLY_FOR_SEVEN_YEARS => $translator->trans("That's more than 5 activities per week for 7 years", locale: $locale),
            self::DAILY_FOR_SEVEN_YEARS => $translator->trans("That's an activity every day for almost 7 years", locale: $locale),
            self::DAILY_FOR_EIGHT_YEARS => $translator->trans("That's an activity every day for over 8 years", locale: $locale),
            self::DAILY_FOR_ELEVEN_YEARS => $translator->trans("That's an activity every day for nearly 11 years", locale: $locale),
            self::DAILY_FOR_FOURTEEN_YEARS => $translator->trans("That's an activity every day for nearly 14 years", locale: $locale),
            self::DAILY_FOR_TWENTY_YEARS => $translator->trans("That's an activity every day for over 20 years", locale: $locale),
            self::DAILY_FOR_TWENTYSEVEN_YEARS => $translator->trans("That's an activity almost every day for 27 years", locale: $locale),
        };
    }
}
