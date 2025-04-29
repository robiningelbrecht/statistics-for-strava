<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ActivityCountPerMonthChart
{
    private function __construct(
        /** @var array<int, array{0: Month, 1: SportType, 2: int}> */
        private array $activityCountPerMonth,
        private Year $year,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<int, array{0: Month, 1: SportType, 2: int}> $activityCountPerMonth
     */
    public static function create(
        array $activityCountPerMonth,
        Year $year,
        TranslatorInterface $translator,
    ): self {
        return new self(
            activityCountPerMonth: $activityCountPerMonth,
            year: $year,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $series = [];
        $monthlyActivityCounts = [];
        $monthlyTotals = array_fill(1, 12, 0);
        $sportTypes = [];

        foreach ($this->activityCountPerMonth as [$month, $sportType, $count]) {
            $monthNumber = $month->getMonth();

            $monthlyActivityCounts[$sportType->value][$monthNumber] = $count;
            $monthlyTotals[$monthNumber] += $count;

            $sportTypes[$sportType->value] = $sportType;
        }

        foreach ($sportTypes as $key => $sportType) {
            $data = [];

            for ($month = 1; $month <= 12; ++$month) {
                $data[] = [
                    'name' => $monthlyTotals[$month] ?? 0,
                    'value' => $monthlyActivityCounts[$key][$month] ?? 0,
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
            $date = sprintf('%s-%02d-01', $this->year, $monthNumber);
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
                'left' => '0%',
                'right' => '0%',
                'bottom' => '0%',
                'top' => '15px',
                'containLabel' => true,
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'min' => 0,
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
