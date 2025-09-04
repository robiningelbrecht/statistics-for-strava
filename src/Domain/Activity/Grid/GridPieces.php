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

    /**
     * @return array<int, array{min: int|float, max?: int|float, color: string, label: string}>
     */
    public function jsonSerialize(): array
    {
        return $this->pieces;
    }
}
