<?php

namespace App\Domain\Activity\Grid;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ActivityGridChart
{
    private function __construct(
        private ActivityGridData $gridData,
        private SerializableDateTime $fromDate,
        private SerializableDateTime $toDate,
        private TranslatorInterface $translator,
    ) {
    }

    public static function create(
        ActivityGridData $gridData,
        SerializableDateTime $fromDate,
        SerializableDateTime $toDate,
        TranslatorInterface $translator,
    ): self {
        return new self(
            gridData: $gridData,
            fromDate: $fromDate,
            toDate: $toDate,
            translator: $translator,
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
                'show' => true,
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
            ],
            'visualMap' => [
                'type' => 'piecewise',
                'selectedMode' => false,
                'left' => 'center',
                'bottom' => 0,
                'orient' => 'horizontal',
                'pieces' => [
                    [
                        'min' => 0,
                        'max' => 0,
                        'color' => '#cdd9e5',
                        'label' => $this->translator->trans('No activities'),
                    ],
                    [
                        'min' => 0.01,
                        'max' => 33,
                        'color' => '#68B34B',
                        'label' => $this->translator->trans('Low').' (0 - 33)',
                    ],
                    [
                        'min' => 33.01,
                        'max' => 66,
                        'color' => '#FAB735',
                        'label' => $this->translator->trans('Medium').' (34 - 66)',
                    ],
                    [
                        'min' => 66.01,
                        'max' => 100,
                        'color' => '#FF8E14',
                        'label' => $this->translator->trans('High').' (67 - 100)',
                    ],
                    [
                        'min' => 100.01,
                        'color' => '#FF0C0C',
                        'label' => $this->translator->trans('Very high').' (> 100)',
                    ],
                ],
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
                        $this->translator->trans('Sun'),
                        $this->translator->trans('Mon'),
                        $this->translator->trans('Tue'),
                        $this->translator->trans('Wed'),
                        $this->translator->trans('Thu'),
                        $this->translator->trans('Fri'),
                        $this->translator->trans('Sat'),
                    ],
                ],
            ],
            'series' => [
                'type' => 'heatmap',
                'coordinateSystem' => 'calendar',
                'data' => $this->gridData,
            ],
        ];
    }
}
