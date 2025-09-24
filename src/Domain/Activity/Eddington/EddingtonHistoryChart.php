<?php

declare(strict_types=1);

namespace App\Domain\Activity\Eddington;

final readonly class EddingtonHistoryChart
{
    private function __construct(
        private Eddington $eddington,
    ) {
    }

    public static function create(
        Eddington $eddington,
    ): self {
        return new self(
            eddington: $eddington,
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $data = [];

        $markPoints = [];
        foreach ($this->eddington->getEddingtonHistory() as $eddington => $on) {
            $data[] = [$on->format('Y-m-d'), $eddington];
            if (0 !== $eddington % 10) {
                continue;
            }
            $markPoints[] = [
                'value' => $eddington,
                'coord' => [
                    $on->format('Y-m-d'),
                    $eddington,
                ],
            ];
        }

        /** @var array<mixed> $currentEddington */
        $currentEddington = end($data);

        if (0 !== $currentEddington[1] % 10) {
            $markPoints[] = [
                'value' => $currentEddington[1],
                'coord' => [
                    $currentEddington[0],
                    $currentEddington[1],
                ],
                'symbolSize' => 50,
                'symbolOffset' => [
                    0,
                    -15,
                ],
                'itemStyle' => [
                    'color' => 'rgba(227, 73, 2, 0.8)',
                ],
                'label' => [
                    'color' => '#FFFFFF',
                ],
            ];
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
                    'splitLine' => [
                        'show' => true,
                        'lineStyle' => [
                            'color' => '#E0E6F1',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'minInterval' => 1,
                    'splitLine' => [
                        'show' => false,
                    ],
                    'min' => 0,
                ],
            ],
            'series' => [
                [
                    'name' => 'Eddington',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => true,
                    'lineStyle' => [
                        'width' => 2,
                    ],
                    'showSymbol' => false,
                    'data' => $data,
                    'markPoint' => [
                        'symbol' => 'pin',
                        'symbolSize' => 0,
                        'symbolOffset' => [
                            0,
                            -20,
                        ],
                        'silent' => true,
                        'itemStyle' => [
                            'color' => 'transparent',
                        ],
                        'label' => [
                            'color' => '#E34902',
                        ],
                        'data' => $markPoints,
                    ],
                ],
            ],
        ];
    }
}
