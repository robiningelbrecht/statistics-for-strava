<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream;

final readonly class CombinedStreamProfileCharts
{
    private const int GRID_HEIGHT = 120;
    private const int TOP_MARGIN = 45;
    private const int BOTTOM_MARGIN = 70;
    private const int GAP = 5;

    /**
     * @param list<CombinedStreamProfileChart> $charts
     */
    private function __construct(
        private array $charts,
    ) {
    }

    /**
     * @param list<CombinedStreamProfileChart> $charts
     */
    public static function fromCharts(array $charts): self
    {
        return new self($charts);
    }

    public function getTotalHeight(): int
    {
        $count = count($this->charts);

        return self::TOP_MARGIN + $count * self::GRID_HEIGHT + ($count - 1) * self::GAP + self::BOTTOM_MARGIN;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $count = count($this->charts);
        $grids = [];
        $xAxes = [];
        $yAxes = [];
        $series = [];
        $xAxisIndices = [];

        foreach ($this->charts as $index => $chart) {
            $partialConfig = $chart->buildForCombined($index, $count);

            $grids[] = $partialConfig['grid'];
            $xAxes[] = $partialConfig['xAxis'];
            $yAxes[] = $partialConfig['yAxis'];
            $series[] = $partialConfig['series'];
            $xAxisIndices[] = $index;
        }

        return [
            'animation' => false,
            'axisPointer' => [
                'link' => [
                    ['xAxisIndex' => 'all'],
                ],
            ],
            'toolbox' => [
                'show' => true,
                'top' => '-5px',
                'feature' => [
                    'dataZoom' => [
                        'show' => true,
                        'yAxisIndex' => 'none',
                    ],
                    'restore' => [
                        'show' => true,
                    ],
                ],
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => 'formatCombinedProfileTooltip',
            ],
            'dataZoom' => [
                [
                    'type' => 'slider',
                    'show' => true,
                    'xAxisIndex' => $xAxisIndices,
                    'throttle' => 100,
                    'showDetail' => false,
                    'minValueSpan' => 60,
                    'dataBackground' => [
                        'lineStyle' => [
                            'opacity' => 0,
                        ],
                        'areaStyle' => [
                            'opacity' => 0,
                        ],
                    ],
                    'selectedDataBackground' => [
                        'lineStyle' => [
                            'opacity' => 0,
                        ],
                        'areaStyle' => [
                            'opacity' => 0,
                        ],
                    ],
                ],
            ],
            'grid' => $grids,
            'xAxis' => $xAxes,
            'yAxis' => $yAxes,
            'series' => $series,
        ];
    }
}
