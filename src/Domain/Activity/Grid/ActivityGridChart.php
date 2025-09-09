<?php

namespace App\Domain\Activity\Grid;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

use function Symfony\Component\Translation\t;

final readonly class ActivityGridChart
{
    private function __construct(
        private ActivityGrid $activityGrid,
        private SerializableDateTime $fromDate,
        private SerializableDateTime $toDate,
    ) {
    }

    public static function create(
        ActivityGrid $activityGrid,
        SerializableDateTime $fromDate,
        SerializableDateTime $toDate,
    ): self {
        return new self(
            activityGrid: $activityGrid,
            fromDate: $fromDate,
            toDate: $toDate,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => true,
            'legend' => [
                'show' => false,
            ],
            'title' => [
                'left' => 'center',
                'text' => sprintf('%s - %s', $this->fromDate->translatedFormat('M Y'), $this->toDate->translatedFormat('M Y')),
                'textStyle' => [
                    'color' => '#111827',
                    'fontSize' => 14,
                ],
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => 'formatActivityGridTooltip',
            ],
            'visualMap' => [
                'type' => 'piecewise',
                'selectedMode' => false,
                'left' => 'center',
                'bottom' => 0,
                'orient' => 'horizontal',
                'pieces' => $this->activityGrid->getPieces(),
            ],
            'calendar' => [
                'left' => 40,
                'cellSize' => [
                    'auto',
                    13,
                ],
                'range' => [$this->fromDate->format('Y-m-d'), $this->toDate->format('Y-m-d')],
                'itemStyle' => [
                    'borderWidth' => 3,
                    'opacity' => 0,
                ],
                'splitLine' => [
                    'show' => false,
                ],
                'yearLabel' => [
                    'show' => false,
                ],
                'dayLabel' => [
                    'firstDay' => 1,
                    'align' => 'right',
                    'fontSize' => 10,
                    'nameMap' => [
                        (string) t('Sun'),
                        (string) t('Mon'),
                        (string) t('Tue'),
                        (string) t('Wed'),
                        (string) t('Thu'),
                        (string) t('Fri'),
                        (string) t('Sat'),
                    ],
                ],
            ],
            'series' => [
                'type' => 'heatmap',
                'coordinateSystem' => 'calendar',
                'data' => $this->activityGrid->getData(),
                'name' => $this->activityGrid->getGridType()->value,
            ],
        ];
    }
}
