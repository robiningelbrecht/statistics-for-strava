<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\Stream\StreamType;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CombinedStreamProfileChart
{
    private function __construct(
        /** @var array<int, int|float> */
        private array $distances,
        /** @var array<int, int|float> */
        private array $yAxisData,
        private StreamType $yAxisStreamType,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, int|float> $distances
     * @param array<int, int|float> $yAxisData
     */
    public static function create(
        array $distances,
        array $yAxisData,
        StreamType $yAxisStreamType,
        TranslatorInterface $translator,
    ): self {
        return new self(
            distances: $distances,
            yAxisData: $yAxisData,
            yAxisStreamType: $yAxisStreamType,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $yAxisLabel = match ($this->yAxisStreamType) {
            StreamType::HEART_RATE => $this->translator->trans('Heart rate'),
            StreamType::CADENCE => $this->translator->trans('Cadence'),
            StreamType::WATTS => $this->translator->trans('Power'),
            StreamType::VELOCITY => $this->translator->trans('Pace'),
            default => $this->yAxisStreamType->value,
        };
        $tooltipSuffix = match ($this->yAxisStreamType) {
            StreamType::HEART_RATE => ' bpm',
            StreamType::CADENCE => ' rpm',
            StreamType::WATTS => ' watt',
            StreamType::VELOCITY => ' min/km',
            default => '',
        };
        $seriesColor = match ($this->yAxisStreamType) {
            StreamType::HEART_RATE => '#ee6666',
            StreamType::CADENCE => '#91cc75',
            StreamType::WATTS => '#73c0de',
            StreamType::VELOCITY => '#fac858',
            default => '#000000',
        };

        return [
            'grid' => [
                'left' => '25px',
                'right' => '0%',
                'bottom' => '0%',
                'top' => '0%',
                'containLabel' => false,
            ],
            'animation' => false,
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => '{c} '.$tooltipSuffix,
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'show' => false,
                ],
                'data' => $this->distances,
                'splitLine' => [
                    'show' => true,
                ],
                'min' => 0,
                'axisTick' => [
                    'show' => false,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => $yAxisLabel,
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 10,
                    'min' => 0,
                    'splitLine' => [
                        'show' => true,
                    ],
                    'axisLabel' => [
                        'show' => false,
                    ],
                ],
            ],
            'series' => [
                [
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
                    ],
                    'data' => $this->yAxisData,
                    'type' => 'line',
                    'symbol' => 'none',
                    'color' => $seriesColor,
                    'smooth' => true,
                    'lineStyle' => [
                        'width' => 0,
                    ],
                    'emphasis' => [
                        'disabled' => true,
                    ],
                    'areaStyle' => [
                        'opacity' => 1,
                    ],
                ],
            ],
        ];
    }
}
