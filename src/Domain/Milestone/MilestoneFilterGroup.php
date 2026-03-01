<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum MilestoneFilterGroup: string implements TranslatableInterface
{
    case FIRST = 'first';
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';
    case MOVING_TIME = 'movingTime';
    case ACTIVITY = 'activity';
    case PERSONAL_BEST = 'personalBest';
    case EDDINGTON = 'eddington';
    case STREAK = 'streak';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::FIRST => $translator->trans('Firsts', locale: $locale),
            self::DISTANCE => $translator->trans('Distance', locale: $locale),
            self::ELEVATION => $translator->trans('Elevation', locale: $locale),
            self::MOVING_TIME => $translator->trans('Moving time', locale: $locale),
            self::ACTIVITY => $translator->trans('Activities', locale: $locale),
            self::PERSONAL_BEST => $translator->trans('Personal bests', locale: $locale),
            self::EDDINGTON => $translator->trans('Eddington', locale: $locale),
            self::STREAK => $translator->trans('Streaks', locale: $locale),
        };
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            self::FIRST => 'number-one',
            self::DISTANCE => 'distance',
            self::ELEVATION => 'elevation',
            self::MOVING_TIME => 'time',
            self::ACTIVITY => 'muscle',
            self::PERSONAL_BEST => 'trophy',
            self::EDDINGTON => 'eddington',
            self::STREAK => 'fire',
        };
    }
}
