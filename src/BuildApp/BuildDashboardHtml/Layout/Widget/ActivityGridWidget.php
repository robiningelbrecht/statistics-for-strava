<?php

declare(strict_types=1);

namespace App\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\ActivityIntensityChart;
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
        return $this->twig->load('html/dashboard/widget/widget--activity-intensity.html.twig')->render([
            'activityIntensityChart' => Json::encode(
                ActivityIntensityChart::create(
                    activityIntensity: $this->activityIntensity,
                    translator: $this->translator,
                    now: $now,
                )->build()
            ),
        ]);
    }
}
