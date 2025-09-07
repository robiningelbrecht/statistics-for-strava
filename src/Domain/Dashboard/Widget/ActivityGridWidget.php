<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\Grid\ActivityGrid;
use App\Domain\Activity\Grid\ActivityGridChart;
use App\Domain\Activity\Grid\ActivityGridType;
use App\Domain\Activity\Grid\FindCaloriesBurnedPerDay\FindCaloriesBurnedPerDay;
use App\Domain\Rewind\FindMovingTimePerDay\FindMovingTimePerDay;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Twig\Environment;

final readonly class ActivityGridWidget implements Widget
{
    public function __construct(
        private ActivityIntensity $activityIntensity,
        private QueryBus $queryBus,
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(array $config): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $fromDate = SerializableDateTime::fromString($now->modify('-11 months')->format('Y-m-01'));
        $toDate = SerializableDateTime::fromString($now->format('Y-m-t 23:59:59'));

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod(
            $fromDate,
            $interval,
            $toDate,
        );

        $years = Years::create(
            startDate: $fromDate,
            endDate: $toDate
        );

        $movingTimePerDay = $this->queryBus->ask(new FindMovingTimePerDay($years))->getMovingTimePerDayInMinutes();
        $caloriesBurnedPerDay = $this->queryBus->ask(new FindCaloriesBurnedPerDay($years))->getCaloriesBurnedPerDay();

        $activityIntensityGrid = ActivityGrid::create(ActivityGridType::INTENSITY);
        $activityMovingTimeGrid = ActivityGrid::create(ActivityGridType::MOVING_TIME);
        $activityCaloriesBurnedGrid = ActivityGrid::create(ActivityGridType::CALORIES_BURNED);

        $activityGrids = [];
        foreach (ActivityGridType::cases() as $activityGridType) {
            $activityGrids[$activityGridType->value] = ActivityGrid::create($activityGridType);
        }

        foreach ($period as $dt) {
            $on = SerializableDateTime::fromDateTimeImmutable($dt);
            $activityGrids[ActivityGridType::INTENSITY->value]->add(
                on: $on,
                value: $this->activityIntensity->calculateForDate($on)
            );
            $activityGrids[ActivityGridType::MOVING_TIME->value]->add(
                on: $on,
                value: $movingTimePerDay[$on->format('Y-m-d')] ?? 0
            );
            $activityGrids[ActivityGridType::CALORIES_BURNED->value]->add(
                on: $on,
                value: $caloriesBurnedPerDay[$on->format('Y-m-d')] ?? 0
            );
        }

        $activityGridsCharts = [];
        foreach (ActivityGridType::cases() as $activityGridType) {
            $activityGridsCharts[$activityGridType->value] = Json::encode(ActivityGridChart::create(
                activityGrid: $activityGrids[$activityGridType->value],
                fromDate: $fromDate,
                toDate: $toDate,
            )->build());
        }

        return $this->twig->load('html/dashboard/widget/widget--activity-grid.html.twig')->render([
            'gridCharts' => $activityGridsCharts,
        ]);
    }
}
