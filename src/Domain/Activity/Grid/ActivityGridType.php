<?php

declare(strict_types=1);

namespace App\Domain\Activity\Grid;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;

enum ActivityGridType: string implements TranslatableInterface
{
    case INTENSITY = 'intensity';
    case MOVING_TIME = 'movingTime';
    case CALORIES_BURNED = 'caloriesBurned';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::INTENSITY => $translator->trans('Activity intensity', locale: $locale),
            self::MOVING_TIME => $translator->trans('Moving time', locale: $locale),
            self::CALORIES_BURNED => $translator->trans('Calories burned', locale: $locale),
        };
    }

    /**
     * @return array<int, array{min: int|float, max?: int|float, color: string, label: string}>
     */
    public function getPieces(): array
    {
        return match ($this) {
            self::INTENSITY => [
                [
                    'min' => 0,
                    'max' => 0,
                    'color' => '#cdd9e5',
                    'label' => (string) t('No activities'),
                ],
                [
                    'min' => 0.01,
                    'max' => 33,
                    'color' => '#68B34B',
                    'label' => t('Low').' (0 - 33)',
                ],
                [
                    'min' => 33.01,
                    'max' => 66,
                    'color' => '#FAB735',
                    'label' => t('Medium').' (34 - 66)',
                ],
                [
                    'min' => 66.01,
                    'max' => 100,
                    'color' => '#FF8E14',
                    'label' => t('High').' (67 - 100)',
                ],
                [
                    'min' => 100.01,
                    'color' => '#FF0C0C',
                    'label' => t('Very high').' (> 100)',
                ],
            ],
            self::MOVING_TIME => [
                [
                    'min' => 0,
                    'max' => 0,
                    'color' => '#cdd9e5',
                    'label' => (string) t('No activities'),
                ],
                [
                    'min' => 0.01,
                    'max' => 30,
                    'color' => '#68B34B',
                    'label' => t('Low').' (0 - 30m)',
                ],
                [
                    'min' => 30.01,
                    'max' => 60,
                    'color' => '#FAB735',
                    'label' => t('Medium').' (31m - 1h)',
                ],
                [
                    'min' => 60.01,
                    'max' => 90,
                    'color' => '#FF8E14',
                    'label' => t('High').' (1h - 1h30)',
                ],
                [
                    'min' => 90.01,
                    'color' => '#FF0C0C',
                    'label' => t('Very high').' (> 1h30)',
                ],
            ],
            self::CALORIES_BURNED => [
                [
                    'min' => 0,
                    'max' => 0,
                    'color' => '#cdd9e5',
                    'label' => (string) t('No activities'),
                ],
                [
                    'min' => 0.01,
                    'max' => 500,
                    'color' => '#68B34B',
                    'label' => t('Low').' (0 - 500)',
                ],
                [
                    'min' => 500.01,
                    'max' => 750,
                    'color' => '#FAB735',
                    'label' => t('Medium').' (501 - 750)',
                ],
                [
                    'min' => 750.01,
                    'max' => 1000,
                    'color' => '#FF8E14',
                    'label' => t('High').' (751 - 1000)',
                ],
                [
                    'min' => 1000.01,
                    'color' => '#FF0C0C',
                    'label' => t('Very high').' (> 1000)',
                ],
            ],
        };
    }
}
