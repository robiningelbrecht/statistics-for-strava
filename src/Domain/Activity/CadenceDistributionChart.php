<?php

declare(strict_types=1);

namespace App\Domain\Activity;

final readonly class CadenceDistributionChart
{
    use ProvideSteppedValue;

    private function __construct(
        /** @var array<int, int> */
        private array $cadenceData,
        private int $averageCadence,
    ) {
    }

    /**
     * @param array<int, int> $cadenceData
     */
    public static function create(
        array $cadenceData,
        int $averageCadence,
        ActivityType $activityType,
    ): self {
        if (in_array($activityType, [ActivityType::RUN, ActivityType::WALK])) {
            $doubledCadenceData = [];
            foreach ($cadenceData as $cadence => $time) {
                $doubledCadenceData[$cadence * 2] = $time;
            }

            return new self(
                cadenceData: $doubledCadenceData,
                averageCadence: $averageCadence * 2,
            );
        }

        return new self(
            cadenceData: $cadenceData,
            averageCadence: $averageCadence,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(): ?array
    {
        if (!$cadenceData = array_filter($this->cadenceData, fn (int $distribution): bool => $distribution > 2)) {
            return null;
        }
        /** @var non-empty-array<int, int> $cadenceData */
        $cadences = array_keys($cadenceData);
        $minCadence = (int) floor(min($cadences) / 10) * 10;
        $maxCadence = (int) max(10, ceil(max($cadences) / 10) * 10);

        foreach (range($minCadence, $maxCadence) as $cadence) {
            if (array_key_exists($cadence, $cadenceData)) {
                continue;
            }
            $cadenceData[$cadence] = 0;
        }
        ksort($cadenceData);

        $step = (int) floor(($maxCadence - $minCadence) / 24) ?: 1;
        $xAxisValues = range($minCadence, $maxCadence, $step);
        if (end($xAxisValues) < $maxCadence) {
            $xAxisValues[] = $maxCadence;
        }

        $totalTimeInSeconds = array_sum($cadenceData);
        $data = [];
        foreach ($xAxisValues as $axisValue) {
            $data[] = array_sum(array_splice($cadenceData, 0, $step)) / $totalTimeInSeconds * 100;
        }

        $yAxisMax = max($data) * 1.2;
        $xAxisValueAverageCadence = array_search($this->findClosestSteppedValue(
            min: $minCadence,
            max: $maxCadence,
            step: $step,
            target: $this->averageCadence,
        ), $xAxisValues);

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
                                'value' => $this->averageCadence,
                                'coord' => [$xAxisValueAverageCadence, $yAxisMax * 0.9],
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
                                ['xAxis' => $xAxisValueAverageCadence, 'yAxis' => 0],
                                ['xAxis' => $xAxisValueAverageCadence, 'yAxis' => $yAxisMax * 0.9],
                            ],
                        ],
                        'silent' => true,
                    ],
                    'markArea' => [
                        'data' => [
                            [
                                [
                                    'itemStyle' => [
                                        'color' => '#3E444D',
                                    ],
                                    'emphasis' => [
                                        'disabled' => true,
                                    ],
                                ],
                                [
                                    'x' => '100%',
                                ],
                            ],
                        ],
                        'silent' => true,
                    ],
                ],
            ],
        ];
    }
}
