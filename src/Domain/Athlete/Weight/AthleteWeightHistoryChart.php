<?php

declare(strict_types=1);

namespace App\Domain\Athlete\Weight;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AthleteWeightHistoryChart
{
    private function __construct(
        /** @var AthleteWeight[] */
        private array $athleteWeights,
        private SerializableDateTime $now,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param AthleteWeight[] $athleteWeights
     */
    public static function create(
        array $athleteWeights,
        SerializableDateTime $now,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
    ): self {
        return new self(
            athleteWeights: $athleteWeights,
            now: $now,
            unitSystem: $unitSystem,
            translator: $translator
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $athleteWeights = $this->athleteWeights;
        ksort($athleteWeights);
        /** @var non-empty-array<float> $weights */
        $weights = array_map(
            fn (AthleteWeight $athleteWeight): float => $athleteWeight->getWeightInKg()->toUnitSystem($this->unitSystem)->toFloat(),
            $athleteWeights,
        );

        /** @var string $lastKey */
        $lastKey = array_key_last($athleteWeights);

        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'top' => '2%',
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
                    'splitLine' => [
                        'show' => true,
                        'lineStyle' => [
                            'color' => '#E0E6F1',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} '.$this->unitSystem->weightSymbol(),
                    ],
                    'min' => floor(min($weights) / 10) * 10,
                ],
            ],
            'series' => [
                [
                    'name' => $this->translator->trans('Weight'),
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...array_map(
                            fn (AthleteWeight $weight): array => [
                                $weight->getOn()->format('Y-m-d'),
                                $weight->getWeightInKg()->toUnitSystem($this->unitSystem)->toFloat(),
                            ],
                            $athleteWeights,
                        ),
                        $this->now->format('Y-m-d') != $athleteWeights[$lastKey]->getOn()->format('Y-m-d') ?
                        [
                            $this->now->format('Y-m-d'),
                            $athleteWeights[$lastKey]->getWeightInKg()->toUnitSystem($this->unitSystem)->toFloat(),
                        ] : [],
                    ],
                ],
            ],
        ];
    }
}
