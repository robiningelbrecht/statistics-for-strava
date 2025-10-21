<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Calendar\MonthlyStats\MonthlyStatsChart;
use App\Domain\Calendar\MonthlyStats\MonthlyStatsContext;
use App\Domain\Dashboard\InvalidDashboardLayout;
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
            ->add('enableLastXYearsByDefault', 10);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (!$configuration->configItemExists('enableLastXYearsByDefault')) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" is required for MonthlyStatsWidget.');
        }
        if (!is_int($configuration->getConfigItem('enableLastXYearsByDefault'))) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" must be an integer.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $activityTypes = $this->activityTypeRepository->findAll();

        $monthlyStatChartsPerContext = [];
        $monthlyStats = $this->queryBus->ask(new FindMonthlyStats());

        /** @var int $enableLastXYearsByDefault */
        $enableLastXYearsByDefault = $configuration->getConfigItem('enableLastXYearsByDefault');
        foreach (MonthlyStatsContext::cases() as $monthlyStatsContext) {
            foreach ($activityTypes as $activityType) {
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

        return $this->twig->load('html/dashboard/widget/widget--monthly-stats.html.twig')->render([
            'monthlyStatsChartsPerContext' => $monthlyStatChartsPerContext,
        ]);
    }
}
