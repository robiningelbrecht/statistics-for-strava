<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\YearlyDistance\FindYearlyStats\FindYearlyStats;
use App\Domain\Strava\Activity\YearlyDistance\FindYearlyStatsPerDay\FindYearlyStatsPerDay;
use App\Domain\Strava\Activity\YearlyDistance\YearlyDistanceChart;
use App\Domain\Strava\Activity\YearlyDistance\YearlyStatistics;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class YearlyDistancesWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private QueryBus $queryBus,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $yearlyDistanceCharts = [];
        $yearlyStatistics = [];
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        $yearlyStats = $this->queryBus->ask(new FindYearlyStats());
        $yearlyStatsPerDay = $this->queryBus->ask(new FindYearlyStatsPerDay());

        foreach ($activitiesPerActivityType as $activityType => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $activityType = ActivityType::from($activityType);
            if (!$activityType->supportsYearlyStats()) {
                continue;
            }

            $yearlyDistanceCharts[$activityType->value] = Json::encode(
                YearlyDistanceChart::create(
                    yearStats: $yearlyStatsPerDay,
                    uniqueYears: $activitiesPerActivityType[$activityType->value]->getUniqueYears(),
                    activityType: $activityType,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                    now: $now
                )->build()
            );

            $yearlyStatistics[$activityType->value] = YearlyStatistics::create(
                yearlyStats: $yearlyStats,
                activityType: $activityType,
                years: $allYears
            );
        }

        return $this->twig->load('html/dashboard/widget/widget--yearly-distances.html.twig')->render([
            'yearlyDistanceCharts' => $yearlyDistanceCharts,
            'yearlyStatistics' => $yearlyStatistics,
        ]);
    }
}
