<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Calendar\MonthlyStats\MonthlyStatsChart;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\StatsContext;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class MonthlyStatsWidget implements Widget
{
    public function __construct(
        private ActivityTypeRepository $activityTypeRepository,
        private QueryBus $queryBus,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 10)
            ->add('metricsDisplayOrder', array_map(fn (StatsContext $context) => $context->value, StatsContext::defaultSortingOrder()));
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (!$configuration->configItemExists('enableLastXYearsByDefault')) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" is required for MonthlyStatsWidget.');
        }
        if (!is_int($configuration->getConfigItem('enableLastXYearsByDefault'))) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" must be an integer.');
        }
        if (!$configuration->configItemExists('metricsDisplayOrder')) {
            throw new InvalidDashboardLayout('Configuration item "metricsDisplayOrder" is required for MonthlyStatsWidget.');
        }
        if (!is_array($configuration->getConfigItem('metricsDisplayOrder'))) {
            throw new InvalidDashboardLayout('Configuration item "metricsDisplayOrder" must be an array.');
        }
        if (3 !== count($configuration->getConfigItem('metricsDisplayOrder'))) {
            throw new InvalidDashboardLayout('Configuration item "metricsDisplayOrder" must contain all 3 metrics.');
        }
        foreach ($configuration->getConfigItem('metricsDisplayOrder') as $metricDisplayOrder) {
            if (!StatsContext::tryFrom($metricDisplayOrder)) {
                throw new InvalidDashboardLayout(sprintf('Configuration item "metricsDisplayOrder" contains invalid value "%s".', $metricDisplayOrder));
            }
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $activityTypes = $this->activityTypeRepository->findAll();

        $monthlyStatChartsPerContext = [];
        $monthlyStats = $this->queryBus->ask(new FindMonthlyStats());

        /** @var int $enableLastXYearsByDefault */
        $enableLastXYearsByDefault = $configuration->getConfigItem('enableLastXYearsByDefault');
        foreach (StatsContext::cases() as $monthlyStatsContext) {
            foreach ($activityTypes as $activityType) {
                if (in_array($monthlyStatsContext, [StatsContext::DISTANCE, StatsContext::ELEVATION]) && !$activityType->supportsDistanceAndElevation()) {
                    continue;
                }

                $monthlyStatChartsPerContext[$monthlyStatsContext->value][$activityType->value] = Json::encode(
                    MonthlyStatsChart::create(
                        activityType: $activityType,
                        monthlyStats: $monthlyStats,
                        context: $monthlyStatsContext,
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        enableLastXYearsByDefault: $enableLastXYearsByDefault
                    )->build()
                );
            }
        }

        /** @var string[] $metricsDisplayOrder */
        $metricsDisplayOrder = $configuration->getConfigItem('metricsDisplayOrder');

        return $this->twig->load('html/dashboard/widget/widget--monthly-stats.html.twig')->render([
            'monthlyStatsChartsPerContext' => $monthlyStatChartsPerContext,
            'metricsDisplayOrder' => array_map(
                StatsContext::from(...),
                $metricsDisplayOrder,
            ),
        ]);
    }
}
