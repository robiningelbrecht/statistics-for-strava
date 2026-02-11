<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream;

use App\Infrastructure\Theme\Theme;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CombinedStreamProfileChart
{
    private function __construct(
        /** @var array<int, int|float> */
        private array $xAxisData,
        private ?string $xAxisLabelSuffix,
        private ?string $xAxisPosition,
        /** @var array<int, int|float> */
        private array $yAxisData,
        private int $maximumNumberOfDigitsOnYAxis,
        private CombinedStreamType $yAxisStreamType,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, int|float> $xAxisData
     * @param array<int, int|float> $yAxisData
     */
    public static function create(
        array $xAxisData,
        ?string $xAxisPosition,
        ?string $xAxisLabelSuffix,
        array $yAxisData,
        int $maximumNumberOfDigitsOnYAxis,
        CombinedStreamType $yAxisStreamType,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
    ): self {
        return new self(
            xAxisData: $xAxisData,
            xAxisLabelSuffix: $xAxisLabelSuffix,
            xAxisPosition: $xAxisPosition,
            yAxisData: $yAxisData,
            maximumNumberOfDigitsOnYAxis: $maximumNumberOfDigitsOnYAxis,
            yAxisStreamType: $yAxisStreamType,
            unitSystem: $unitSystem,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        if ([] === $this->yAxisData) {
            throw new \RuntimeException('yAxisData data cannot be empty');
        }
        $yAxisSuffix = $this->yAxisStreamType->getSuffix($this->unitSystem);

        [$min, $max] = [min($this->yAxisData), max($this->yAxisData)];
        $margin = ($max - $min) * 0.1;
        $maxYAxis = (int) ceil($max + $margin);
        $minYAxis = max($min, 0);

        if (CombinedStreamType::ALTITUDE === $this->yAxisStreamType) {
            $minYAxis = (int) floor($min - $margin);
        }

        return [
            'grid' => [
                'left' => match ($this->maximumNumberOfDigitsOnYAxis) {
                    default => '65px',
                    4 => '75px',
                    5 => '85px',
                },
                'right' => '20px',
                'bottom' => Theme::POSITION_BOTTOM === $this->xAxisPosition && [] !== $this->xAxisData ? '70px' : '0%',
                'top' => Theme::POSITION_TOP === $this->xAxisPosition && [] !== $this->xAxisData ? '45px' : '0%',
                'containLabel' => false,
            ],
            'animation' => false,
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => CombinedStreamType::PACE !== $this->yAxisStreamType ?
                    '<strong>{c}</strong> '.$yAxisSuffix : 'formatPace',
            ],
            'toolbox' => [
                'show' => Theme::POSITION_TOP === $this->xAxisPosition,
                'top' => '-5px',
                'feature' => [
                    'dataZoom' => [
                        'show' => true,
                        'yAxisIndex' => 'none',
                    ],
                    'restore' => [
                        'show' => true,
                    ],
                ],
            ],
            'dataZoom' => [
                [
                    'type' => 'slider',
                    'show' => Theme::POSITION_BOTTOM === $this->xAxisPosition,
                    'throttle' => 100,
                    'showDetail' => false,
                    'minValueSpan' => 60,
                    'dataBackground' => [
                        'lineStyle' => [
                            'opacity' => 0,
                        ],
                        'areaStyle' => [
                            'opacity' => 0,
                        ],
                    ],
                    'selectedDataBackground' => [
                        'lineStyle' => [
                            'opacity' => 0,
                        ],
                        'areaStyle' => [
                            'opacity' => 0,
                        ],
                    ],
                ],
            ],
            'xAxis' => [
                'position' => $this->xAxisPosition,
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'show' => !is_null($this->xAxisPosition) && [] !== $this->xAxisData,
                    'formatter' => '{value} '.$this->xAxisLabelSuffix,
                ],
                'data' => $this->xAxisData,
                'min' => 0,
                'axisTick' => [
                    'show' => false,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => $this->yAxisStreamType->trans($this->translator),
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 10,
                    'min' => $minYAxis,
                    'max' => $maxYAxis,
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'show' => true,
                        'customValues' => [$minYAxis, $maxYAxis],
                        'color' => '#aaa',
                        'verticalAlignMaxLabel' => 'top',
                        'verticalAlignMinLabel' => 'bottom',
                    ],
                ],
            ],
            'series' => [
                [
                    'markArea' => [
                        'data' => [
                            [
                                [
                                    'xAxis' => 'min',
                                    'itemStyle' => [
                                        'color' => '#3E444D',
                                    ],
                                    'emphasis' => [
                                        'disabled' => true,
                                    ],
                                ],
                                [
                                    'xAxis' => 'max',
                                ],
                            ],
                        ],
                    ],
                    'data' => $this->yAxisData,
                    'type' => 'line',
                    'showSymbol' => false,
                    'progressive' => 5000,
                    'progressiveThreshold' => 10000,
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
                        'origin' => 'start',
                    ],
                ],
            ],
        ];
    }
}
