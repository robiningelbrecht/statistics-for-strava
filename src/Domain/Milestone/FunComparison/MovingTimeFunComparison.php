<?php

declare(strict_types=1);

namespace App\Domain\Milestone\FunComparison;

use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use Symfony\Contracts\Translation\TranslatorInterface;

enum MovingTimeFunComparison: string implements FunComparison
{
    case FULL_WORK_DAY = 'timeFullWorkDay';
    case LOTR_EXTENDED = 'timeLotrExtended';
    case FULL_DAY = 'timeFullDay';
    case TWO_FULL_DAYS = 'timeTwoFullDays';
    case THREE_FULL_DAYS = 'timeThreeFullDays';
    case FOUR_DAYS = 'timeFourDays';
    case FULL_WEEK = 'timeFullWeek';
    case TWO_WEEKS = 'timeTwoWeeks';
    case THREE_WEEKS = 'timeThreeWeeks';
    case FULL_MONTH = 'timeFullMonth';
    case FORTY_ONE_DAYS = 'timeFortyOneDays';
    case TWO_MONTHS = 'timeTwoMonths';
    case THREE_MONTHS = 'timeThreeMonths';
    case SIX_MONTHS = 'timeSixMonths';
    case FULL_YEAR = 'timeFullYear';

    public static function resolve(Hour $movingTime): ?self
    {
        $hours = $movingTime->toFloat();

        return match (true) {
            $hours >= 8_760 => self::FULL_YEAR,
            $hours >= 4_380 => self::SIX_MONTHS,
            $hours >= 2_160 => self::THREE_MONTHS,
            $hours >= 1_440 => self::TWO_MONTHS,
            $hours >= 1_000 => self::FORTY_ONE_DAYS,
            $hours >= 744 => self::FULL_MONTH,
            $hours >= 500 => self::THREE_WEEKS,
            $hours >= 336 => self::TWO_WEEKS,
            $hours >= 168 => self::FULL_WEEK,
            $hours >= 100 => self::FOUR_DAYS,
            $hours >= 72 => self::THREE_FULL_DAYS,
            $hours >= 48 => self::TWO_FULL_DAYS,
            $hours >= 24 => self::FULL_DAY,
            $hours >= 12 => self::LOTR_EXTENDED,
            $hours >= 8 => self::FULL_WORK_DAY,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::FULL_WORK_DAY => $translator->trans("That's a full work day of exercise", locale: $locale),
            self::LOTR_EXTENDED => $translator->trans("That's like watching all Lord of the Rings extended editions back to back", locale: $locale),
            self::FULL_DAY => $translator->trans("That's a full day of non-stop movement", locale: $locale),
            self::TWO_FULL_DAYS => $translator->trans("That's 2 full days of non-stop activity", locale: $locale),
            self::THREE_FULL_DAYS => $translator->trans("That's 3 full days of non-stop movement", locale: $locale),
            self::FOUR_DAYS => $translator->trans("That's more than 4 days of continuous activity", locale: $locale),
            self::FULL_WEEK => $translator->trans("That's a full week of non-stop movement", locale: $locale),
            self::TWO_WEEKS => $translator->trans("That's 2 full weeks of non-stop activity", locale: $locale),
            self::THREE_WEEKS => $translator->trans("That's almost 21 days of continuous movement", locale: $locale),
            self::FULL_MONTH => $translator->trans("That's a full month of non-stop activity", locale: $locale),
            self::FORTY_ONE_DAYS => $translator->trans("That's more than 41 days of non-stop movement", locale: $locale),
            self::TWO_MONTHS => $translator->trans("That's 2 months without stopping", locale: $locale),
            self::THREE_MONTHS => $translator->trans("That's about 3 months of continuous activity", locale: $locale),
            self::SIX_MONTHS => $translator->trans("That's over 6 months of non-stop movement", locale: $locale),
            self::FULL_YEAR => $translator->trans("That's more than a full year of non-stop movement", locale: $locale),
        };
    }
}
