<?php

namespace App\Domain\Activity;

use App\Domain\Calendar\Week;
use App\Domain\Calendar\Weeks;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class WeeklyDistanceTimeChart
{
    private function __construct(
        private Activities $activities,
        private UnitSystem $unitSystem,
        private ActivityType $activityType,
        private TranslatorInterface $translator,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        Activities $activities,
        UnitSystem $unitSystem,
        ActivityType $activityType,
        TranslatorInterface $translator,
        SerializableDateTime $now,
    ): self {
        return new self(
            activities: $activities,
            unitSystem: $unitSystem,
            activityType: $activityType,
            translator: $translator,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $weeks = Weeks::create(
            startDate: $this->activities->getFirstActivityStartDate(),
            now: $this->now
        );
        $zoomValueSpan = 10;
        $data = $this->getData($weeks);
        if (empty(array_filter($data[0])) && empty(array_filter($data[1]))) {
            return [];
        }

        $xAxisLabels = [];
        /** @var Week $week */
        foreach ($weeks as $week) {
            if ($week == $weeks->getFirst() || in_array($week->getLabel(), $xAxisLabels)) {
                $xAxisLabels[] = '';
                continue;
            }
            $xAxisLabels[] = $week->getLabel();
        }

        $series = [];
        $serie = [
            'type' => 'line',
            'smooth' => false,
            'label' => [
                'show' => true,
                'rotate' => -45,
            ],
            'lineStyle' => [
                'width' => 1,
            ],
            'symbolSize' => 6,
            'showSymbol' => true,
            'areaStyle' => [
                'opacity' => 0.3,
                'color' => 'rgba(227, 73, 2, 0.3)',
            ],
            'emphasis' => [
                'focus' => 'series',
            ],
        ];

        $unitSymbol = $this->unitSystem->distanceSymbol();

        if (!empty(array_filter($data[0]))) {
            $series[] = array_merge_recursive(
                $serie,
                [
                    'name' => $this->translator->trans('Distance / week'),
                    'data' => $data[0],
                    'yAxisIndex' => 0,
                    'label' => [
                        'formatter' => '{@[1]} '.$unitSymbol,
                    ],
                ],
            );
        }

        if (!empty(array_filter($data[1]))) {
            $series[] = array_merge_recursive(
                $serie,
                [
                    'name' => $this->translator->trans('Time / week'),
                    'data' => $data[1],
                    'yAxisIndex' => 1,
                    'label' => [
                        'formatter' => '{@[1]} h',
                    ],
                ],
            );
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'color' => [
                '#E34902',
            ],
            'grid' => [
                'left' => '10px',
                'right' => '10px',
                'bottom' => '50px',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
                'selectedMode' => 'single',
            ],
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'startValue' => count($weeks),
                    'endValue' => count($weeks) - $zoomValueSpan,
                    'minValueSpan' => $zoomValueSpan,
                    'maxValueSpan' => $zoomValueSpan,
                    'brushSelect' => false,
                    'zoomLock' => true,
                ],
                [
                ],
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'interval' => 0,
                    ],
                    'data' => $xAxisLabels,
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
                        'formatter' => '{value} '.$unitSymbol,
                    ],
                ],
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} h',
                    ],
                ],
            ],
            'series' => $series,
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(Weeks $weeks): array
    {
        $distancePerWeek = [];
        $timePerWeek = [];

        /** @var Week $week */
        foreach ($weeks as $week) {
            $distancePerWeek[$week->getId()] = 0;
            $timePerWeek[$week->getId()] = 0;
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $week = $activity->getStartDate()->getYearAndWeekNumberString();
            if (!array_key_exists($week, $distancePerWeek)) {
                continue;
            }

            $distance = $activity->getDistance()->toUnitSystem($this->unitSystem);
            $distancePerWeek[$week] += $distance->toFloat();
            $timePerWeek[$week] += $activity->getMovingTimeInSeconds();
        }

        $distancePerWeek = array_map(
            fn (float|int $distance) => round($distance, $this->activityType->getDistancePrecision()),
            $distancePerWeek
        );
        $timePerWeek = array_map(fn (int $time) => round($time / 3600, 1), $timePerWeek);

        return [array_values($distancePerWeek), array_values($timePerWeek)];
    }
}
