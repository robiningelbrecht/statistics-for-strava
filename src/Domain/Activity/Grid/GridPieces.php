<?php

namespace App\Domain\Activity\Grid;

use function Symfony\Component\Translation\t;

final readonly class GridPieces implements \JsonSerializable
{
    private function __construct(
        /** @var array<int, array{min: int|float, max?: int|float, color: string, label: string}> */
        private array $pieces,
    ) {
    }

    /**
     * @param array<int, array{min: int|float, max?: int|float, color: string, label: string}> $pieces
     */
    public static function create(array $pieces): self
    {
        return new self($pieces);
    }

    public static function forActivityIntensity(): self
    {
        return new self([
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
        ]);
    }

    public static function forActivityDuration(): self
    {
        return new self([
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
        ]);
    }

    public static function forActivityCalories(): self
    {
        return new self([
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
        ]);
    }

    /**
     * @return array<int, array{min: int|float, max?: int|float, color: string, label: string}>
     */
    public function jsonSerialize(): array
    {
        return $this->pieces;
    }
}
