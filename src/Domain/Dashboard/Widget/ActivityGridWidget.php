<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\Grid\ActivityGrid;
use App\Domain\Activity\Grid\ActivityGridChart;
use App\Domain\Activity\Grid\FindCaloriesBurnedPerDay\FindCaloriesBurnedPerDay;
use App\Domain\Activity\Grid\GridPieces;
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

        $movingTimePerDay = $this->queryBus->ask(new FindMovingTimePerDay($years))->getMovingTimePerDay();
        $caloriesBurnedPerDay = $this->queryBus->ask(new FindCaloriesBurnedPerDay($years))->getCaloriesBurnedPerDay();

        $activityIntensityGrid = ActivityGrid::create(GridPieces::forActivityIntensity());
        $activityMovingTimeGrid = ActivityGrid::create(GridPieces::forActivityMovingTime());
        $activityCaloriesBurnedGrid = ActivityGrid::create(GridPieces::forActivityCaloriesBurned());

        foreach ($period as $dt) {
            $on = SerializableDateTime::fromDateTimeImmutable($dt);
            $activityIntensityGrid->add(
                on: $on,
                value: $this->activityIntensity->calculateForDate($on)
            );
            $activityMovingTimeGrid->add(
                on: $on,
                value: $movingTimePerDay[$on->format('Y-m-d')] ?? 0
            );
            $activityCaloriesBurnedGrid->add(
                on: $on,
                value: $caloriesBurnedPerDay[$on->format('Y-m-d')] ?? 0
            );
        }

        return $this->twig->load('html/dashboard/widget/widget--activity-grid.html.twig')->render([
            'activityIntensityChart' => Json::encode(
                ActivityGridChart::create(
                    activityGrid: $activityIntensityGrid,
                    fromDate: $fromDate,
                    toDate: $toDate,
                )->build()
            ),
        ]);
    }
}
