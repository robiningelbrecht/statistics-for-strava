<?php

declare(strict_types=1);

namespace App\Domain\Activity\YearlyDistance;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\YearlyDistance\FindYearlyStatsPerDay\FindYearlyStatsPerDayResponse;
use App\Domain\Dashboard\StatsContext;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class YearlyDistanceChart
{
    private function __construct(
        private FindYearlyStatsPerDayResponse $yearStats,
        private Years $uniqueYears,
        private ActivityType $activityType,
        private StatsContext $context,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
        private SerializableDateTime $now,
        private int $enableLastXYearsByDefault,
    ) {
    }

    public static function create(
        FindYearlyStatsPerDayResponse $yearStats,
        Years $uniqueYears,
        ActivityType $activityType,
        StatsContext $context,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
        SerializableDateTime $now,
        int $enableLastXYearsByDefault,
    ): self {
        return new self(
            yearStats: $yearStats,
            uniqueYears: $uniqueYears,
            activityType: $activityType,
            context: $context,
            unitSystem: $unitSystem,
            translator: $translator,
            now: $now,
            enableLastXYearsByDefault: $enableLastXYearsByDefault,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $months = [
            1 => $this->translator->trans('Jan'),
            2 => $this->translator->trans('Feb'),
            3 => $this->translator->trans('Mar'),
            4 => $this->translator->trans('Apr'),
            5 => $this->translator->trans('May'),
            6 => $this->translator->trans('Jun'),
            7 => $this->translator->trans('Jul'),
            8 => $this->translator->trans('Aug'),
            9 => $this->translator->trans('Sep'),
            10 => $this->translator->trans('Oct'),
            11 => $this->translator->trans('Nov'),
            12 => $this->translator->trans('Dec'),
        ];

        $xAxisLabels = [];
        foreach ($months as $month) {
            $xAxisLabels = [...$xAxisLabels, ...array_fill(0, 31, $month)];
        }

        $series = [];
        $selectedSeries = [];
        $delta = 1;
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->uniqueYears as $year) {
            if ($delta <= $this->enableLastXYearsByDefault) {
                $selectedSeries[$year->toInt()] = true;
            } else {
                $selectedSeries[$year->toInt()] = false;
            }
            ++$delta;

            $series[(string) $year] = [
                'name' => (string) $year,
                'type' => 'line',
                'smooth' => true,
                'showSymbol' => false,
                'data' => [],
            ];

            $previousValue = match ($this->context) {
                StatsContext::MOVING_TIME => Hour::zero(),
                StatsContext::DISTANCE => Kilometer::zero()->toUnitSystem($this->unitSystem),
                StatsContext::ELEVATION => Meter::zero()->toUnitSystem($this->unitSystem),
            };
            foreach ($months as $month => $label) {
                for ($dayOfMonth = 1; $dayOfMonth <= 31; ++$dayOfMonth) {
                    $date = SerializableDateTime::fromString(sprintf(
                        '%04d-%02d-%02d',
                        $year->toInt(),
                        $month,
                        $dayOfMonth),
                    );

                    if ($date->isAfter($this->now)) {
                        break 2;
                    }

                    $value = match ($this->context) {
                        StatsContext::MOVING_TIME => $this->yearStats->getMovingTimeFor($date, $this->activityType)?->toHour(),
                        StatsContext::DISTANCE => $this->yearStats->getDistanceFor($date, $this->activityType)?->toUnitSystem($this->unitSystem),
                        StatsContext::ELEVATION => $this->yearStats->getElevationFor($date, $this->activityType)?->toUnitSystem($this->unitSystem),
                    };

                    if (!$value) {
                        $value = $previousValue;
                    }
                    $previousValue = $value;
                    $series[(string) $year]['data'][] = round($value->toFloat());
                }
            }
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'grid' => [
                'left' => '0',
                'right' => '10px',
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
                'selected' => $selectedSeries,
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
                'valueFormatter' => match ($this->context) {
                    StatsContext::MOVING_TIME => 'formatHours',
                    StatsContext::DISTANCE => 'formatDistance',
                    StatsContext::ELEVATION => 'formatElevation',
                },
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 50,
                    'axisLabel' => [
                        'formatter' => match ($this->context) {
                            StatsContext::MOVING_TIME => '{value}h',
                            StatsContext::DISTANCE => '{value}'.$this->unitSystem->distanceSymbol(),
                            StatsContext::ELEVATION => '{value}'.$this->unitSystem->elevationSymbol(),
                        },
                    ],
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
