<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\ActivityTypeRepository;
use App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Strava\Calendar\MonthlyStats\MonthlyStatsChart;
use App\Domain\Strava\Calendar\MonthlyStats\MonthlyStatsContext;
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
                    enableLastXYearsByDefault: $enableLastXYearsByDefault,
                )->build()
            );
        }

        return $this->twig->load('html/dashboard/widget/widget--monthly-stats.html.twig')->render([
            'monthlyStatsCharts' => $monthlyStatCharts,
        ]);
    }
}
