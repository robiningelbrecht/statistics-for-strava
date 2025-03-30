<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class ElevationProfileChart
{
    private function __construct(
        private array $distances,
        private array $altitudes,
        private UnitSystem $unitSystem,
    ) {
    }

    public static function create(
        array $distances,
        array $altitudes,
        UnitSystem $unitSystem,
    ): self {
        return new self(
            distances: $distances,
            altitudes: $altitudes,
            unitSystem: $unitSystem,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $distanceSymbol = $this->unitSystem->distanceSymbol();
        $elevationSymbol = $this->unitSystem->elevationSymbol();

        return [
            'grid' => [
                'left' => '9%',
                'right' => '0%',
                'bottom' => '7%',
                'containLabel' => false,
            ],
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'formatter' => '{value} '.$distanceSymbol,
                ],
                'data' => $this->distances,
                'splitLine' => [
                    'show' => true,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => true,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} '.$elevationSymbol,
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
                    'data' => $this->altitudes,
                    'type' => 'line',
                    'name' => 'Elevation',
                    'symbol' => 'none',
                    'color' => '#D9D9D9',
                    'smooth' => true,
                    'emphasis' => [
                        'disabled' => true,
                    ],
                    'areaStyle' => [
                    ],
                    'lineStyle' => [
                        'width' => 0,
                    ],
                ],
            ],
        ];
    }
}
