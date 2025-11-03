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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::MOVING_TIME => $translator->trans('Time', locale: $locale),
            self::DISTANCE => $translator->trans('Distance', locale: $locale),
            self::ELEVATION => $translator->trans('Elevation', locale: $locale),
        };
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            self::MOVING_TIME => 'time',
            self::DISTANCE => 'distance',
            self::ELEVATION => 'elevation',
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
        ];
    }
}
