<?php

namespace App\Domain\Activity\Eddington;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class EddingtonDaysNeededChart
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
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $data = [];

        $xAxisData = [];
        $unitDistance = $this->unitSystem->distanceSymbol();

        foreach ($this->eddington->getDaysToCompleteForFutureNumbers() as $eddingtonNumber => $daysNeeded) {
            $xAxisData[] = $eddingtonNumber.$unitDistance;
            $data[] = $daysNeeded;
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'left' => '0',
                'right' => '10px',
                'bottom' => '10px',
                'top' => '15px',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'data' => $xAxisData,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'minInterval' => 1,
                    'min' => 0,
                ],
            ],
            'series' => [
                [
                    'name' => 'Days needed',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'lineStyle' => [
                        'width' => 2,
                    ],
                    'showSymbol' => false,
                    'data' => $data,
                ],
            ],
        ];
    }
}
