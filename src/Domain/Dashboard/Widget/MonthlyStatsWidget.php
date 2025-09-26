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
            ->add('enableLastXYearsByDefault', 10)
            ->add('context', MonthlyStatsContext::DISTANCE->value);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (!$configuration->configItemExists('enableLastXYearsByDefault')) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" is required for MonthlyStatsWidget.');
        }
        if (!is_int($configuration->getConfigItem('enableLastXYearsByDefault'))) {
            throw new InvalidDashboardLayout('Configuration item "enableLastXYearsByDefault" must be an integer.');
        }
        if (!$configuration->configItemExists('context')) {
            throw new InvalidDashboardLayout('Configuration item "context" is required for MonthlyStatsWidget.');
        }
        if (!is_string($configuration->getConfigItem('context'))) {
            throw new InvalidDashboardLayout('Configuration item "context" must be a string.');
        }
        if (!MonthlyStatsContext::tryFrom($configuration->getConfigItem('context'))) {
            throw new InvalidDashboardLayout(sprintf('Invalid context "%s" provided for MonthlyStatsWidget.', $configuration->getConfigItem('context')));
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $activityTypes = $this->activityTypeRepository->findAll();

        $monthlyStatCharts = [];
        $monthlyStats = $this->queryBus->ask(new FindMonthlyStats());

        /** @var string $context */
        $context = $configuration->getConfigItem('context');
        /** @var int $enableLastXYearsByDefault */
        $enableLastXYearsByDefault = $configuration->getConfigItem('enableLastXYearsByDefault');
        foreach ($activityTypes as $activityType) {
            $monthlyStatCharts[$activityType->value] = Json::encode(
                MonthlyStatsChart::create(
                    activityType: $activityType,
                    monthlyStats: $monthlyStats,
                    context: MonthlyStatsContext::from($context),
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                    enableLastXYearsByDefault: $enableLastXYearsByDefault
                )->build()
            );
        }

        return $this->twig->load('html/dashboard/widget/widget--monthly-stats.html.twig')->render([
            'monthlyStatsCharts' => $monthlyStatCharts,
            'context' => MonthlyStatsContext::from($context),
        ]);
    }
}
