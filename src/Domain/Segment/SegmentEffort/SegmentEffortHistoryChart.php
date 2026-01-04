<?php

namespace App\Domain\Segment\SegmentEffort;

final readonly class SegmentEffortHistoryChart
{
    private function __construct(
        private SegmentEfforts $segmentEfforts,
    ) {
    }

    public static function create(
        SegmentEfforts $segmentEfforts,
    ): self {
        return new self($segmentEfforts);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $data = [];
        foreach ($this->segmentEfforts as $segmentEffort) {
            $data[] = [$segmentEffort->getStartDateTime()->format('Y-m-d'), $segmentEffort->getElapsedTimeInSeconds()];
        }

        return [
            'backgroundColor' => '#ffffff',
            'animation' => false,
            'grid' => [
                'top' => '30px',
                'left' => '10px',
                'right' => '10px',
                'bottom' => '50px',
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
                            'day'=> '{d}',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                ],
            ],
            'series' => [
                [
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'scatter',
                    'data' => $data,
                ],
            ],
            'toolbox' => [
                'show' => true,
                'feature' => [
                    'restore' => [
                        'show' => true,
                    ],
                ],
            ],
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'start' => 0,
                    'end' => 100,
                    'brushSelect' => true,
                    'zoomLock' => false,
                    'zoomOnMouseWheel' => false,
                ],
                [
                ],
            ],
        ];
    }
}
