<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class YearlyDistanceChart
{
    private function __construct(
        private ActivityRepository $activityRepository,
        private Years $uniqueYears,
        private ActivityType $activityType,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        ActivityRepository $activityRepository,
        Years $uniqueYears,
        ActivityType $activityType,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
        SerializableDateTime $now,
    ): self {
        return new self(
            activityRepository: $activityRepository,
            uniqueYears: $uniqueYears,
            activityType: $activityType,
            unitSystem: $unitSystem,
            translator: $translator,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $months = [
            '01' => $this->translator->trans('Jan'),
            '02' => $this->translator->trans('Feb'),
            '03' => $this->translator->trans('Mar'),
            '04' => $this->translator->trans('Apr'),
            '05' => $this->translator->trans('May'),
            '06' => $this->translator->trans('Jun'),
            '07' => $this->translator->trans('Jul'),
            '08' => $this->translator->trans('Aug'),
            '09' => $this->translator->trans('Sep'),
            '10' => $this->translator->trans('Oct'),
            '11' => $this->translator->trans('Nov'),
            '12' => $this->translator->trans('Dec'),
        ];

        $xAxisLabels = [];
        foreach ($months as $month) {
            $xAxisLabels = [...$xAxisLabels, ...array_fill(0, 31, $month)];
        }

        $series = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->uniqueYears as $year) {
            $series[(string) $year] = [
                'name' => (string) $year,
                'type' => 'line',
                'smooth' => true,
                'showSymbol' => false,
                'data' => [],
            ];

            $runningSum = 0;
            foreach ($months as $monthNumber => $label) {
                for ($i = 0; $i < 31; ++$i) {
                    $date = SerializableDateTime::fromString(sprintf(
                        '%s-%s-%s',
                        $year,
                        $monthNumber,
                        str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT))
                    );
                    $activitiesOnThisDay = $this->activityRepository->findByStartDate($date, $this->activityType);

                    if ($date->isAfter($this->now)) {
                        break 2;
                    }

                    $runningSum += $activitiesOnThisDay->sum(
                        fn (Activity $activity) => $activity->getDistance()->toUnitSystem($this->unitSystem)->toFloat()
                    );
                    $series[(string) $year]['data'][] = round($runningSum);
                }
            }
        }

        $unitSymbol = $this->unitSystem->distanceSymbol();

        return [
            'animation' => true,
            'backgroundColor' => null,
            'grid' => [
                'left' => '40px',
                'right' => '4%',
                'bottom' => '50px',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'interval' => 31,
                    ],
                    'data' => $xAxisLabels,
                ],
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => $this->translator->trans('Distance in {unit}', ['{unit}' => $unitSymbol]),
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 50,
                ],
            ],
            'toolbox' => [
                'show' => true,
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
                    'type' => 'inside',
                    'start' => 0,
                    'end' => 100,
                    'brushSelect' => true,
                    'zoomLock' => false,
                    'zoomOnMouseWheel' => false,
                ],
                [
                ],
            ],
            'series' => array_values($series),
        ];
    }
}
