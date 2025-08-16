<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Calendar\Month;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DistancePerMonthChart
{
    private function __construct(
        /** @var array<int, array{0: int, 1: SportType, 2: Kilometer}> */
        private array $distancePerMonth,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, array{0: int, 1: SportType, 2: Kilometer}> $distancePerMonth
     */
    public static function create(
        array $distancePerMonth,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
    ): self {
        return new self(
            distancePerMonth: $distancePerMonth,
            unitSystem: $unitSystem,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $series = [];
        $unitSymbol = $this->unitSystem->distanceSymbol();
        $monthlyDistances = [];
        $monthlyTotals = array_fill(1, 12, 0);
        $sportTypes = [];

        foreach ($this->distancePerMonth as [$monthNumber, $sportType, $distance]) {
            $convertedDistance = round($distance->toUnitSystem($this->unitSystem)->toFloat());

            $monthlyDistances[$sportType->value][$monthNumber] = $convertedDistance;
            $monthlyTotals[$monthNumber] += $convertedDistance;

            $sportTypes[$sportType->value] = $sportType;
        }

        foreach ($sportTypes as $key => $sportType) {
            $data = [];

            for ($month = 1; $month <= 12; ++$month) {
                $data[] = [
                    'name' => $monthlyTotals[$month] ?? 0,
                    'value' => $monthlyDistances[$key][$month] ?? 0,
                ];
            }

            $series[] = [
                'name' => $sportType->trans($this->translator),
                'type' => 'bar',
                'stack' => 'total',
                'label' => [
                    'show' => false,
                    'position' => 'top',
                    'formatter' => '{b}',
                ],
                'data' => $data,
            ];
        }

        // Enable label only on top series.
        if (!empty($series)) {
            $series[array_key_last($series)]['label']['show'] = true;
        }

        // X axis labels.
        $xAxisLabels = [];
        for ($monthNumber = 1; $monthNumber <= 12; ++$monthNumber) {
            // We don't care about the year, we only need this to display the month label.
            $date = sprintf('%s-%02d-01', 2025, $monthNumber);
            $month = Month::fromDate(SerializableDateTime::fromString($date));
            $xAxisLabels[] = $month->getShortLabelWithoutYear();
        }

        return [
            'animation' => false,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'type' => 'shadow',
                ],
            ],
            'grid' => [
                'left' => '15px',
                'right' => '0%',
                'bottom' => '0%',
                'top' => '15px',
                'containLabel' => true,
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'min' => 0,
                    'name' => $this->translator->trans('Distance in {unit}', ['{unit}' => $unitSymbol]),
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 40,
                ],
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $xAxisLabels,
                    'axisTick' => [
                        'show' => false,
                    ],
                ],
            ],
            'series' => $series,
        ];
    }
}
