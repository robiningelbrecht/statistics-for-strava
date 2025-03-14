<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Months;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class DistancePerMonthPerGearChart
{
    private function __construct(
        private Gears $gears,
        private Activities $activities,
        private UnitSystem $unitSystem,
        private Months $months,
    ) {
    }

    public static function create(
        Gears $gearCollection,
        Activities $activityCollection,
        UnitSystem $unitSystem,
        Months $months,
    ): self {
        return new self(
            gears: $gearCollection,
            activities: $activityCollection,
            unitSystem: $unitSystem,
            months: $months
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $gears = $this->gears->sortByIsRetired();

        $xAxisValues = [];
        $distancePerGearAndMonth = [];
        /** @var Month $month */
        foreach ($this->months as $month) {
            $xAxisValues[] = $month->getLabel();
            /** @var Gear $gear */
            foreach ($gears as $gear) {
                $distancePerGearAndMonth[(string) $gear->getId()][$month->getId()] = 0;
            }
        }
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            if (!$activity->getGearId()) {
                continue;
            }
            $month = $activity->getStartDate()->format(Month::MONTH_ID_FORMAT);
            $distancePerGearAndMonth[(string) $activity->getGearId()][$month] += $activity->getDistance()->toUnitSystem($this->unitSystem)->toFloat();
        }

        foreach ($distancePerGearAndMonth as $gearId => $months) {
            $distancePerGearAndMonth[$gearId] = array_map('round', $months);
        }

        $series = [];
        $selectedSeries = [];

        $unitSymbol = $this->unitSystem->distanceSymbol();
        foreach ($gears as $gear) {
            $distanceInLastThreeMonths = array_sum(array_slice($distancePerGearAndMonth[(string) $gear->getId()], -3, 3));

            $gearName = $gear->getSanitizedName();
            $selectedSeries[$gearName] = $distanceInLastThreeMonths > 0;
            $series[] = [
                'name' => $gearName,
                'type' => 'bar',
                'barGap' => 0,
                'emphasis' => [
                    'focus' => 'series',
                ],
                'label' => [
                    'show' => true,
                    'position' => 'insideBottom',
                    'verticalAlign' => 'middle',
                    'align' => 'left',
                    'color' => '#000',
                    'rotate' => 90,
                    'distance' => 15,
                    'formatter' => sprintf('{distance|{c} %s} - {a}', $unitSymbol),
                    'rich' => [
                        'distance' => [
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                        ],
                    ],
                ],
                'data' => array_values($distancePerGearAndMonth[(string) $gear->getId()]),
            ];
        }

        return [
            'backgroundColor' => '#ffffff',
            'animation' => true,
            'color' => ['#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '50px',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'type' => 'none',
                ],
            ],
            'legend' => [
                'selected' => $selectedSeries,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'data' => $xAxisValues,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                ],
            ],
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'startValue' => count($xAxisValues) - 4,
                    'endValue' => count($xAxisValues),
                    'minValueSpan' => 4,
                    'maxValueSpan' => 4,
                    'brushSelect' => false,
                    'zoomLock' => true,
                ],
                [
                ],
            ],
            'series' => $series,
        ];
    }
}
