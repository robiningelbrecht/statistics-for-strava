<?php

declare(strict_types=1);

namespace App\Domain\Calendar\MonthlyStats;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum MonthlyStatsContext: string implements TranslatableInterface
{
    case MOVING_TIME = 'movingTime';
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';

    public function getUrlSlug(): string
    {
        return match ($this) {
            MonthlyStatsContext::MOVING_TIME => 'time',
            default => $this->value,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MOVING_TIME => $translator->trans('Moving time', locale: $locale),
            self::DISTANCE => $translator->trans('Distance', locale: $locale),
            self::ELEVATION => $translator->trans('Elevation', locale: $locale),
        };
    }
}
