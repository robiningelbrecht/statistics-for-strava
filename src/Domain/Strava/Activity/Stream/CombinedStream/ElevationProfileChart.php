<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ElevationProfileChart
{
    private function __construct(
        /** @var array<int, int|float> */
        private array $distances,
        /** @var array<int, int|float> */
        private array $altitudes,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, int|float> $distances
     * @param array<int, int|float> $altitudes
     */
    public static function create(
        array $distances,
        array $altitudes,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
    ): self {
        return new self(
            distances: $distances,
            altitudes: $altitudes,
            unitSystem: $unitSystem,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $distanceSymbol = $this->unitSystem->distanceSymbol();
        $elevationSymbol = $this->unitSystem->elevationSymbol();

        if (empty($this->altitudes)) {
            return [];
        }

        $maxYAxis = round(max($this->altitudes) * 1.2);

        return [
            'grid' => [
                'left' => '25px',
                'right' => '0%',
                'bottom' => '20px',
                'top' => '10px',
                'containLabel' => false,
            ],
            'animation' => false,
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => '{c} '.$elevationSymbol,
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
                    'name' => $this->translator->trans('Elevation'),
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 10,
                    'max' => $maxYAxis,
                    'min' => 0,
                    'splitLine' => [
                        'show' => true,
                    ],
                    'axisLabel' => [
                        'show' => false,
                        'formatter' => '{value} '.$elevationSymbol,
                        'customValues' => [0, $maxYAxis],
                        'hideOverlap' => true,
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
                    'symbol' => 'none',
                    'color' => '#D9D9D9',
                    'smooth' => true,
                    'emphasis' => [
                        'disabled' => true,
                    ],
                    'areaStyle' => [
                    ],
                ],
            ],
        ];
    }
}
