<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream;

final readonly class PowerOutputChart
{
    private function __construct(
        private BestPowerOutputs $bestPowerOutputs,
    ) {
    }

    public static function create(
        BestPowerOutputs $bestPowerOutputs,
    ): self {
        return new self($bestPowerOutputs);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $series = [];
        $maxPowerOutput = 100;
        foreach ($this->bestPowerOutputs as $bestPowerOutputs) {
            /** @var PowerOutputs $powerOutputs */
            [$description, $powerOutputs] = $bestPowerOutputs;
            $scalarPowerOutputs = $powerOutputs->map(fn (PowerOutput $powerOutput): int => $powerOutput->getPower());
            $series[] = [
                'type' => 'line',
                'name' => $description,
                'smooth' => true,
                'symbol' => 'none',
                'data' => array_values($scalarPowerOutputs),
            ];

            $maxPowerOutput = max($maxPowerOutput, ...$scalarPowerOutputs);
        }

        $yAxisMaxValue = ceil($maxPowerOutput / 100) * 100;
        $yAxisInterval = $yAxisMaxValue / 5;

        return [
            'animation' => true,
            'backgroundColor' => null,
            'grid' => [
                'left' => '0%',
                'right' => '0%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'interval' => 0,
                ],
                'axisTick' => [
                    'show' => false,
                ],
                'data' => [
                    '1s',
                    '5s',
                    '',
                    '15s',
                    '',
                    '',
                    '1m',
                    '2m',
                    '',
                    '',
                    '5m',
                    '',
                    '8m',
                    '',
                    '',
                    '20m',
                    '30m',
                    '',
                    '',
                    '1h',
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'axisLabel' => [
                        'formatter' => '{value} w',
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => $yAxisInterval,
                ],
            ],
            'series' => $series,
        ];
    }
}
