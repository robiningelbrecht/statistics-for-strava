<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar\MonthlyStats;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStatsResponse;
use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MonthlyStatsChart
{
    private function __construct(
        private ActivityType $activityType,
        private FindMonthlyStatsResponse $monthlyStats,
        private MonthlyStatsContext $context,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
        private int $enableLastXYearsByDefault,
    ) {
    }

    public static function create(
        ActivityType $activityType,
        FindMonthlyStatsResponse $monthlyStats,
        MonthlyStatsContext $context,
        UnitSystem $unitSystem,
        TranslatorInterface $translator,
        ?int $enableLastXYearsByDefault = null,
    ): self {
        return new self(
            activityType: $activityType,
            monthlyStats: $monthlyStats,
            context: $context,
            unitSystem: $unitSystem,
            translator: $translator,
            enableLastXYearsByDefault: $enableLastXYearsByDefault ?? 20
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $series = [];

        $firstMonth = $this->monthlyStats->getFirstMonthFor($this->activityType);
        $lastMonth = $this->monthlyStats->getLastMonthFor($this->activityType);
        $years = Years::create(
            startDate: $firstMonth->getFirstDay(),
            endDate: $lastMonth->getFirstDay(),
        )->reverse();

        $selectedSeries = [];
        $delta = 1;
        /** @var Year $year */
        foreach ($years as $year) {
            if ($delta <= $this->enableLastXYearsByDefault) {
                $selectedSeries[$year->toInt()] = true;
            } else {
                $selectedSeries[$year->toInt()] = false;
            }
            ++$delta;

            $data = [];
            /** @var Month $month */
            foreach ($year->getMonths() as $month) {
                $stats = $this->monthlyStats->getForMonthAndActivityType(
                    month: $month,
                    activityType: $this->activityType
                );

                if (is_null($stats)) {
                    if ($month->isBefore($firstMonth) || $month->isAfter($lastMonth)) {
                        $data[] = null;
                    } else {
                        $data[] = 0;
                    }
                } else {
                    $data[] = MonthlyStatsContext::TIME === $this->context ?
                        $stats['movingTime']->toHour()->toInt() : $stats['distance']->toUnitSystem($this->unitSystem)->toFloat();
                }
            }

            $series[] = [
                'name' => (string) $year->toInt(),
                'type' => 'line',
                'smooth' => true,
                'data' => $data,
            ];
        }

        return [
            'backgroundColor' => null,
            'animation' => false,
            'grid' => [
                'top' => '50px',
                'left' => '0',
                'right' => '30px',
                'bottom' => '2%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'valueFormatter' => MonthlyStatsContext::TIME === $this->context ? 'formatHours' : 'formatDistance',
            ],
            'legend' => [
                'selected' => $selectedSeries,
                'data' => array_map(
                    fn (Year $year) => (string) $year->toInt(),
                    $years->toArray()
                ),
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => [
                    $this->translator->trans('Jan'),
                    $this->translator->trans('Feb'),
                    $this->translator->trans('Mar'),
                    $this->translator->trans('Apr'),
                    $this->translator->trans('May'),
                    $this->translator->trans('Jun'),
                    $this->translator->trans('Jul'),
                    $this->translator->trans('Aug'),
                    $this->translator->trans('Sep'),
                    $this->translator->trans('Oct'),
                    $this->translator->trans('Nov'),
                    $this->translator->trans('Dec'),
                ],
                'axisPointer' => [
                    'type' => 'shadow',
                ],
            ],
            'yAxis' => [
                'type' => 'value',
                'axisLabel' => [
                    'formatter' => '{value}'.(MonthlyStatsContext::TIME === $this->context ? 'h' : $this->unitSystem->distanceSymbol()),
                ],
            ],
            'series' => $series,
        ];
    }
}
