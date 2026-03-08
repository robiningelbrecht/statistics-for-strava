<?php

declare(strict_types=1);

namespace App\Domain\Dashboard;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum StatsContext: string implements TranslatableInterface
{
    case MOVING_TIME = 'movingTime';
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';
    case LOAD = 'load';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MOVING_TIME => $translator->trans('Time', locale: $locale),
            self::DISTANCE => $translator->trans('Distance', locale: $locale),
            self::ELEVATION => $translator->trans('Elevation', locale: $locale),
            self::LOAD => $translator->trans('Load', locale: $locale),
        };
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            self::MOVING_TIME => 'time',
            self::DISTANCE => 'distance',
            self::ELEVATION => 'elevation',
            self::LOAD => 'load',
        };
    }

    /**
     * @return StatsContext[]
     */
    public static function defaultSortingOrder(): array
    {
        return [
            self::DISTANCE,
            self::MOVING_TIME,
            self::ELEVATION,
            self::LOAD,
        ];
    }

    /**
     * @return StatsContext[]
     */
    public static function defaultChartSortingOrder(): array
    {
        return [
            self::DISTANCE,
            self::MOVING_TIME,
            self::ELEVATION,
        ];
    }
}
