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
        $distanceSymbol = $this->unitSystem->distanceSymbol();
        $maxYAxis = ceil(max($this->yAxisData) * 1.1);
        $yAxisSuffix = $this->yAxisStreamType->getSuffix($this->unitSystem);

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
                    'min' => 0,
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
                    'data' => $this->yAxisData,
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
}
