<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ActivityStartTimesChart
{
    private function __construct(
        /** @var array<int, int> */
        private array $activityStartTimes,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, int> $activityStartTimes
     */
    public static function create(
        array $activityStartTimes,
        TranslatorInterface $translator,
    ): self {
        return new self(
            activityStartTimes: $activityStartTimes,
            translator: $translator,
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
                'formatter' => '{b}h: {c} '.$this->translator->trans('activities'),
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
