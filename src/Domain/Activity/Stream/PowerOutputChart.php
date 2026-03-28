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
            [$description, $powerOutputs] = $bestPowerOutputs;
            assert($powerOutputs instanceof PowerOutputs);

            $data = $powerOutputs->map(
                fn (PowerOutput $powerOutput): array => [$powerOutput->getTimeIntervalInSeconds(), $powerOutput->getPower()]
            );

            if ([] === $data) {
                continue; // @codeCoverageIgnore
            }

            $powers = $powerOutputs->map(fn (PowerOutput $powerOutput): int => $powerOutput->getPower());
            $maxPowerOutput = max($maxPowerOutput, ...$powers);

            $series[] = [
                'type' => 'line',
                'name' => $description,
                'smooth' => 0.3,
                'symbol' => 'none',
                'data' => $data,
            ];
        }

        $yAxisMaxValue = (int) (ceil($maxPowerOutput / 100) * 100);
        $yAxisInterval = (int) ($yAxisMaxValue / 5);

        return [
            'animation' => true,
            'backgroundColor' => null,
            'grid' => [
                'left' => '2px',
                'right' => '10px',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
                'formatter' => 'callback:formatPowerDurationTooltip',
            ],
            'xAxis' => [
                'type' => 'log',
                'min' => 1,
                'max' => 3600,
                'axisLabel' => [
                    'formatter' => 'callback:formatDuration',
                ],
                'splitLine' => [
                    'lineStyle' => ['type' => 'dashed'],
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
