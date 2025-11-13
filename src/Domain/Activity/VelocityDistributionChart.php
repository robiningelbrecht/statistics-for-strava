<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPer100Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;

final readonly class VelocityDistributionChart
{
    private function __construct(
        /** @var array<int, float> */
        private array $velocityData,
        private KmPerHour $averageSpeed,
        private SportType $sportType,
        private UnitSystem $unitSystem,
    ) {
    }

    /**
     * @param array<int, float> $velocityData
     */
    public static function create(
        array $velocityData,
        KmPerHour $averageSpeed,
        SportType $sportType,
        UnitSystem $unitSystem,
    ): self {
        return new self(
            velocityData: $velocityData,
            averageSpeed: $averageSpeed,
            sportType: $sportType,
            unitSystem: $unitSystem,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        /** @var non-empty-array<int, int> $velocityData */
        $velocityData = $this->velocityData;
        $velocities = array_keys($velocityData);
        $minVelocity = (int) floor(min($velocities) / 10) * 10;
        $maxVelocity = (int) ceil(max($velocities) / 10) * 10;

        foreach (range($minVelocity, $maxVelocity) as $velocity) {
            if (array_key_exists($velocity, $this->velocityData)) {
                continue;
            }
            $velocityData[$velocity] = 0;
        }
        ksort($velocityData);

        $step = (int) floor(($maxVelocity - $minVelocity) / 24) ?: 1;
        $xAxisValues = range($minVelocity, $maxVelocity, $step);
        if (end($xAxisValues) < $maxVelocity) {
            $xAxisValues[] = $maxVelocity;
        }

        $totalTimeInSeconds = array_sum($velocityData);
        $data = [];
        foreach ($xAxisValues as $axisValue) {
            $data[] = array_sum(array_splice($velocityData, 0, $step)) / $totalTimeInSeconds * 100;
        }
        // @phpstan-ignore-next-line
        $yAxisMax = max($data) * 1.2;
        $xAxisValueAverageVelocity = array_search(floor($this->averageSpeed->toFloat() / $step) * $step, $xAxisValues);
        $velocityUnitPreference = $this->sportType->getVelocityDisplayPreference();

        $convertedAverageVelocity = match (true) {
            $velocityUnitPreference instanceof SecPer100Meter => round($this->averageSpeed->toMetersPerSecond()->toSecPerKm()->toSecPer100Meter()->toFloat(), 1),
            $velocityUnitPreference instanceof SecPerKm && UnitSystem::METRIC === $this->unitSystem => round($this->averageSpeed->toMetersPerSecond()->toSecPerKm()->toFloat(), 1),
            $velocityUnitPreference instanceof SecPerKm && UnitSystem::IMPERIAL === $this->unitSystem => round($this->averageSpeed->toMetersPerSecond()->toSecPerKm()->toSecPerMile()->toFloat(), 1),
            UnitSystem::IMPERIAL === $this->unitSystem => round($this->averageSpeed->toMph()->toFloat(), 1),
            default => round($this->averageSpeed->toFloat(), 1),
        };

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
                                'value' => $convertedAverageVelocity,
                                'coord' => [$xAxisValueAverageVelocity, $yAxisMax * 0.9],
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
                                ['xAxis' => $xAxisValueAverageVelocity, 'yAxis' => 0],
                                ['xAxis' => $xAxisValueAverageVelocity, 'yAxis' => $yAxisMax * 0.9],
                            ],
                        ],
                        'silent' => true,
                    ],
                    'markArea' => [
                        'data' => [
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
                        ],
                        'silent' => true,
                    ],
                ],
            ],
        ];
    }
}
