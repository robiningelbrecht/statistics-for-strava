<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\Grid\ActivityGrid;
use App\Domain\Activity\Grid\ActivityGridChart;
use App\Domain\Activity\Grid\GridPieces;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class ActivityGridWidget implements Widget
{
    public function __construct(
        private ActivityIntensity $activityIntensity,
        private Environment $twig,
        private TranslatorInterface $translator,
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
        $activityGrid = ActivityGrid::create(GridPieces::forActivityIntensity());
        $fromDate = SerializableDateTime::fromString($now->modify('-11 months')->format('Y-m-01'));
        $toDate = SerializableDateTime::fromString($now->format('Y-m-t 23:59:59'));

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod(
            $fromDate,
            $interval,
            $toDate,
        );

        foreach ($period as $dt) {
            $on = SerializableDateTime::fromDateTimeImmutable($dt);
            $activityGrid->add(
                on: $on,
                value: $this->activityIntensity->calculateForDate($on)
            );
        }

        return $this->twig->load('html/dashboard/widget/widget--activity-grid.html.twig')->render([
            'activityIntensityChart' => Json::encode(
                ActivityGridChart::create(
                    activityGrid: $activityGrid,
                    fromDate: $fromDate,
                    toDate: $toDate,
                    translator: $this->translator,
                )->build()
            ),
        ]);
    }
}
