<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\YearlyDistance\FindYearlyStats\FindYearlyStats;
use App\Domain\Activity\YearlyDistance\FindYearlyStatsPerDay\FindYearlyStatsPerDay;
use App\Domain\Activity\YearlyDistance\YearlyDistanceChart;
use App\Domain\Activity\YearlyDistance\YearlyStatistics;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\StatsContext;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class YearlyStatsWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private QueryBus $queryBus,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 10);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (!$configuration->configItemExists('enableLastXYearsByDefault')) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" is required for YearlyDistancesWidget.');
        }
        if (!is_int($configuration->getConfigItem('enableLastXYearsByDefault'))) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" must be an integer.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $yearlyStatChartsPerContext = [];
        $yearlyStatistics = [];
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        $yearlyStats = $this->queryBus->ask(new FindYearlyStats());
        $yearlyStatsPerDay = $this->queryBus->ask(new FindYearlyStatsPerDay());

        /** @var int $enableLastXYearsByDefault */
        $enableLastXYearsByDefault = $configuration->getConfigItem('enableLastXYearsByDefault');

        foreach ($activitiesPerActivityType as $activityType => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $activityType = ActivityType::from($activityType);
            foreach (StatsContext::cases() as $yearlyStatsContext) {
                if (in_array($yearlyStatsContext, [StatsContext::DISTANCE, StatsContext::ELEVATION]) && !$activityType->supportsDistanceAndElevation()) {
                    continue;
                }

                $yearlyStatChartsPerContext[$yearlyStatsContext->value][$activityType->value] = Json::encode(
                    YearlyDistanceChart::create(
                        yearStats: $yearlyStatsPerDay,
                        uniqueYears: $activitiesPerActivityType[$activityType->value]->getUniqueYears(),
                        activityType: $activityType,
                        context: $yearlyStatsContext,
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        now: $now,
                        enableLastXYearsByDefault: $enableLastXYearsByDefault
                    )->build()
                );
            }

            $yearlyStatistics[$activityType->value] = YearlyStatistics::create(
                yearlyStats: $yearlyStats,
                activityType: $activityType,
                years: $allYears
            );
        }

        return $this->twig->load('html/dashboard/widget/widget--yearly-distances.html.twig')->render([
            'yearlyStatsChartsPerContext' => $yearlyStatChartsPerContext,
            'yearlyStatistics' => $yearlyStatistics,
        ]);
    }
}
