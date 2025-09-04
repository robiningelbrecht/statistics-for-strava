<?php

namespace App\Domain\Activity\Eddington;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

use function Symfony\Component\Translation\t;

final readonly class EddingtonChart
{
    private function __construct(
        private Eddington $eddington,
        private UnitSystem $unitSystem,
    ) {
    }

    public static function create(
        Eddington $eddington,
        UnitSystem $unitSystem,
    ): self {
        return new self(
            eddington: $eddington,
            unitSystem: $unitSystem,
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $longestDistanceInADay = $this->eddington->getLongestDistanceInADay();
        /** @var non-empty-array<mixed> $timesCompletedData */
        $timesCompletedData = $this->eddington->getTimesCompletedData();
        $eddingtonNumber = $this->eddington->getNumber();

        $yAxisMaxValue = max(ceil(max($timesCompletedData) / 30) * 30, $longestDistanceInADay);
        $yAxisInterval = ceil(($yAxisMaxValue / 5) / 30) * 30;

        $timesCompletedDataForChart = $timesCompletedData;
        $timesCompletedDataForChart[$eddingtonNumber] = [
            'value' => $timesCompletedData[$eddingtonNumber],
            'itemStyle' => [
                'color' => 'rgba(227, 73, 2, 0.8)',
            ],
        ];

        $unitDistance = Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol();

        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'legend' => [
                'show' => true,
                'selectedMode' => false,
            ],
            'xAxis' => [
                'data' => array_map(fn (int $distance) => $distance.$unitDistance, range(1, $longestDistanceInADay)),
                'type' => 'category',
                'axisTick' => [
                    'alignWithLabel' => true,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => true,
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => $yAxisInterval,
                ],
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => $yAxisInterval,
                ],
            ],
            'series' => [
                [
                    'name' => (string) t('Times completed'),
                    'yAxisIndex' => 0,
                    'type' => 'bar',
                    'label' => [
                        'show' => false,
                    ],
                    'showBackground' => false,
                    'itemStyle' => [
                        'color' => 'rgba(227, 73, 2, 0.3)',
                    ],
                    'markPoint' => [
                        'symbol' => 'pin',
                        'symbolOffset' => [
                            0,
                            -5,
                        ],
                        'itemStyle' => [
                            'color' => 'rgba(227, 73, 2, 0.8)',
                        ],
                        'data' => [
                            [
                                'value' => $eddingtonNumber,
                                'coord' => [
                                    $eddingtonNumber - 1,
                                    $timesCompletedData[$eddingtonNumber] - 1,
                                ],
                            ],
                        ],
                    ],
                    'data' => array_values($timesCompletedDataForChart),
                ],
                [
                    'name' => (string) t('Eddington'),
                    'yAxisIndex' => 1,
                    'zlevel' => 1,
                    'type' => 'line',
                    'smooth' => false,
                    'showSymbol' => false,
                    'label' => [
                        'show' => false,
                    ],
                    'showBackground' => false,
                    'itemStyle' => [
                        'color' => '#E34902',
                    ],
                    'data' => range(1, $longestDistanceInADay),
                ],
            ],
        ];
    }
}
