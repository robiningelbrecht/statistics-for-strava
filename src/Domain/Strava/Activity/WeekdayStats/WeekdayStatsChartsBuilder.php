<?php

namespace App\Domain\Strava\Activity\WeekdayStats;

final readonly class WeekdayStatsChartsBuilder
{
    private function __construct(
        private WeekdayStats $weekdayStats,
    ) {
    }

    public static function create(
        WeekdayStats $weekdayStats,
    ): self {
        return new self($weekdayStats);
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => false,
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{d}%',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{weekday|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'weekday' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
                    ],
                    'data' => $this->getData(),
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(): array
    {
        $data = [];
        foreach ($this->weekdayStats->getData() as $weekday => $statistic) {
            $data[] = [
                'value' => $statistic['percentage'],
                'name' => $weekday,
            ];
        }

        return $data;
    }
}
