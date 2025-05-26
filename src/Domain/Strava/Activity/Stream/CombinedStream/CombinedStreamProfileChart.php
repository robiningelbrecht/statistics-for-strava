<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CombinedStreamProfileChart
{
    private function __construct(
        /** @var array<int, int|float> */
        private array $distances,
        /** @var array<int, int|float> */
        private array $yAxisData,
        private CombinedStreamType $yAxisStreamType,
        private UnitSystem $unitSystem,
        private bool $showXAxis,
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
        CombinedStreamType $yAxisStreamType,
        UnitSystem $unitSystem,
        bool $showXAxis,
        TranslatorInterface $translator,
    ): self {
        return new self(
            distances: $distances,
            yAxisData: $yAxisData,
            yAxisStreamType: $yAxisStreamType,
            unitSystem: $unitSystem,
            showXAxis: $showXAxis,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        if (empty($this->yAxisData)) {
            throw new \RuntimeException('yAxisData data cannot be empty');
        }
        $yData = $this->yAxisData;
        if (CombinedStreamType::ALTITUDE === $this->yAxisStreamType) {
            $yData = $this->smoothData($yData);
        }
        $distanceSymbol = $this->unitSystem->distanceSymbol();
        $yAxisSuffix = $this->yAxisStreamType->getSuffix($this->unitSystem);

        if (CombinedStreamType::ALTITUDE === $this->yAxisStreamType) {
            $actualMin = min($yData);
            $actualMax = max($yData);
            $range = $actualMax - $actualMin;
            $margin = $range * 0.1;
            $minYAxis = (int) floor($actualMin - $margin);
            $maxYAxis = (int) ceil($actualMax + $margin);
        } else {
            $minYAxis = 0;
            $maxYAxis = (int) ceil(max($yData) * 1.1);
        }

        return [
            'grid' => [
                'left' => '25px',
                'right' => '0%',
                'bottom' => $this->showXAxis ? '20px' : '0%',
                'top' => '0%',
                'containLabel' => false,
            ],
            'animation' => false,
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => CombinedStreamType::PACE !== $this->yAxisStreamType ?
                    '<strong>{c}</strong> '.$yAxisSuffix : 'formatPace',
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'show' => $this->showXAxis,
                    'formatter' => '{value} '.$distanceSymbol,
                ],
                'data' => $this->distances,
                'min' => 0,
                'axisTick' => [
                    'show' => false,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => sprintf('%s (%s)', $this->yAxisStreamType->trans($this->translator), $yAxisSuffix),
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 10,
                    'min' => $minYAxis,
                    'max' => $maxYAxis,
                    'splitLine' => [
                        'show' => false,
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
                    'data' => $yData,
                    'type' => 'line',
                    'symbol' => 'none',
                    'color' => $this->yAxisStreamType->getSeriesColor(),
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

    /**
     * Smooths the given data array with a simple moving average.
     *
     * @param array<int, int|float> $data
     * @param int $window  Number of points in the moving average window (odd number)
     * @return array<float>
     */
    private function smoothData(array $data, int $window = 5): array
    {
        $count = count($data);
        if ($count === 0 || $window < 1) {
            return $data;
        }
        $half = intdiv($window, 2);
        $smoothed = [];
        for ($i = 0; $i < $count; ++$i) {
            $start = max(0, $i - $half);
            $end = min($count - 1, $i + $half);
            $sum = 0;
            $n = $end - $start + 1;
            for ($j = $start; $j <= $end; ++$j) {
                $sum += $data[$j];
            }
            $smoothed[] = $sum / $n;
        }
        return $smoothed;
    }
}
