<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Ftp\Ftp;

final readonly class PowerDistributionChart
{
    use ProvideSteppedValue;

    private function __construct(
        /** @var array<int, int> */
        private array $powerData,
        private int $averagePower,
        private ?Ftp $ftp,
    ) {
    }

    /**
     * @param array<int, int> $powerData
     */
    public static function create(
        array $powerData,
        int $averagePower,
        ?Ftp $ftp,
    ): self {
        return new self(
            powerData: $powerData,
            averagePower: $averagePower,
            ftp: $ftp,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): ?array
    {
        // Filter out any possible spikes. Data points with less than 2 occurrences are filtered out.
        if (!$powerData = array_filter($this->powerData, fn (int $distribution): bool => $distribution > 2)) {
            return null;
        }
        /** @var non-empty-array<int, int> $powerData */
        $powers = array_keys($powerData);
        $minPower = 0;
        $maxPower = (int) ceil(max($powers) / 100) * 100;

        if ($maxPower <= 0) {
            // Something fishy is going on.
            return null;
        }

        foreach (range($minPower, $maxPower) as $power) {
            if (array_key_exists($power, $powerData)) {
                continue;
            }
            $powerData[$power] = 0;
        }
        ksort($powerData);

        $step = (int) floor($maxPower / 24) ?: 1;
        $xAxisValues = range($minPower, $maxPower, $step);
        if (end($xAxisValues) < $maxPower) {
            $xAxisValues[] = $maxPower;
        }

        $totalTimeInSeconds = array_sum($powerData);
        $data = [];
        foreach ($xAxisValues as $axisValue) {
            $data[] = array_sum(array_splice($powerData, 0, $step)) / $totalTimeInSeconds * 100;
        }

        $yAxisMax = max($data) * 1.2;
        $xAxisValueAveragePower = array_search($this->findClosestSteppedValue(
            min: $minPower,
            max: $maxPower,
            step: $step,
            target: $this->averagePower,
        ), $xAxisValues);

        $markAreas = [
            [
                [
                    'itemStyle' => [
                        'color' => '#303030',
                    ],
                ],
                [
                    'x' => '100%',
                ],
            ],
        ];

        // Calculate the mark areas to display the zones.
        if ($ftp = $this->ftp?->getFtp()->getValue()) {
            $oneWattPercentage = 100 / $maxPower;

            $markAreas = [
                [
                    [
                        'itemStyle' => [
                            'color' => '#303030',
                        ],
                        'x' => '0%',
                    ],
                    [
                        'x' => $ftp * 0.55 * $oneWattPercentage.'%',
                    ],
                ],
                [
                    [
                        'itemStyle' => [
                            'color' => '#2888FC',
                        ],
                        'x' => $ftp * 0.55 * $oneWattPercentage.'%',
                    ],
                    [
                        'x' => $ftp * 0.75 * $oneWattPercentage.'%',
                    ],
                ],
                [
                    [
                        'itemStyle' => [
                            'color' => '#56C15A',
                        ],
                        'x' => $ftp * 0.75 * $oneWattPercentage.'%',
                    ],
                    [
                        'x' => $ftp * 0.87 * $oneWattPercentage.'%',
                    ],
                ],
                [
                    [
                        'itemStyle' => [
                            'color' => '#FECE38',
                        ],
                        'x' => $ftp * 0.87 * $oneWattPercentage.'%',
                    ],
                    [
                        'x' => $ftp * 0.94 * $oneWattPercentage.'%',
                    ],
                ],
                [
                    [
                        'itemStyle' => [
                            'color' => '#FF6531',
                        ],
                        'x' => $ftp * 0.94 * $oneWattPercentage.'%',
                    ],
                    [
                        'x' => $ftp * 1.05 * $oneWattPercentage.'%',
                    ],
                ],
                [
                    [
                        'itemStyle' => [
                            'color' => '#FF2F04',
                        ],
                        'x' => $ftp * 1.05 * $oneWattPercentage.'%',
                    ],
                    [
                        'x' => $ftp * 10000 * $oneWattPercentage.'%',
                    ],
                ],
            ];
        }

        return [
            'grid' => [
                'left' => '1%',
                'right' => '1%',
                'bottom' => '7%',
                'height' => '325px',
                'containLabel' => false,
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => $xAxisValues,
                'axisTick' => [
                    'show' => false,
                ],
                'axisLine' => [
                    'show' => false,
                ],
                'axisLabel' => [
                    'interval' => 2,
                    'showMinLabel' => true,
                ],
            ],
            'yAxis' => [
                'show' => false,
                'min' => 0,
                'max' => $yAxisMax,
            ],
            'series' => [
                [
                    'data' => $data,
                    'type' => 'bar',
                    'cursor' => 'default',
                    'barCategoryGap' => 1,
                    'itemStyle' => [
                        'color' => '#fff',
                        'borderRadius' => [25, 25, 0, 0],
                    ],
                    'markPoint' => [
                        'silent' => true,
                        'animation' => false,
                        'symbolSize' => 45,
                        'symbol' => 'roundRect',
                        'itemStyle' => [
                            'color' => '#7F7F7F',
                        ],
                        'label' => [
                            'formatter' => "{label|AVG}\n{sub|{c}}",
                            'lineHeight' => 15,
                            'rich' => [
                                'label' => [
                                    'fontWeight' => 'bold',
                                ],
                                'sub' => [
                                    'fontSize' => 12,
                                ],
                            ],
                        ],
                        'data' => [
                            [
                                'value' => $this->averagePower,
                                'coord' => [$xAxisValueAveragePower, $yAxisMax * 0.9],
                            ],
                        ],
                    ],
                    'markLine' => [
                        'symbol' => 'none',
                        'animation' => false,
                        'lineStyle' => [
                            'type' => 'solid',
                            'width' => 2,
                            'color' => '#7F7F7F',
                        ],
                        'label' => [
                            'show' => false,
                        ],
                        'data' => [
                            [
                                ['xAxis' => $xAxisValueAveragePower, 'yAxis' => 0],
                                ['xAxis' => $xAxisValueAveragePower, 'yAxis' => $yAxisMax * 0.9],
                            ],
                        ],
                        'silent' => true,
                    ],
                    'markArea' => [
                        'data' => $markAreas,
                        'silent' => true,
                    ],
                ],
            ],
        ];
    }
}
