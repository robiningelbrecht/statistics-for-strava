<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

use function Symfony\Component\Translation\t;

final readonly class ActivityStartTimesChart
{
    private function __construct(
        /** @var array<int, int> */
        private array $activityStartTimes,
    ) {
    }

    /**
     * @param array<int, int> $activityStartTimes
     */
    public static function create(
        array $activityStartTimes,
    ): self {
        return new self(
            activityStartTimes: $activityStartTimes,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $data = [];
        $xAxisLabels = [];

        for ($startTime = 0; $startTime <= 23; ++$startTime) {
            $xAxisLabels[] = $startTime;
            $data[] = 0;
        }

        foreach ($this->activityStartTimes as $startTime => $numberOfActivities) {
            $data[$startTime] = $numberOfActivities;
        }

        return [
            'animation' => false,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => '{b}h: {c} '.t('activities'),
            ],
            'grid' => [
                'left' => '0%',
                'right' => '15px',
                'bottom' => '0%',
                'top' => '15px',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $xAxisLabels,
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value}h',
                    ],
                    'splitLine' => [
                        'show' => false,
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'min' => 0,
                ],
            ],
            'series' => [
                [
                    'color' => [
                        '#E34902',
                    ],
                    'label' => [
                        'show' => true,
                    ],
                    'areaStyle' => [
                        'opacity' => 0.3,
                        'color' => 'rgba(227, 73, 2, 0.3)',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'lineStyle' => [
                        'width' => 2,
                    ],
                    'showSymbol' => true,
                    'data' => $data,
                ],
            ],
        ];
    }
}
