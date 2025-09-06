<?php

namespace App\Domain\Activity\DaytimeStats;

use function Symfony\Component\Translation\t;

final readonly class DaytimeStatsCharts
{
    private function __construct(
        private DaytimeStats $daytimeStats,
    ) {
    }

    public static function create(
        DaytimeStats $daytimeStats,
    ): self {
        return new self(
            $daytimeStats,
        );
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
                        'formatter' => "{daytime|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'daytime' => [
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
        foreach ($this->daytimeStats->getData() as $statistic) {
            $data[] = [
                'value' => $statistic['percentage'],
                'name' => $statistic['daytime']->getEmoji().' '.t($statistic['daytime']->value),
            ];
        }

        return $data;
    }
}
