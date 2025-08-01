<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Eddington\EddingtonCalculator;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class EddingtonWidget implements Widget
{
    public function __construct(
        private EddingtonCalculator $eddingtonCalculator,
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

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        $eddingtons = array_filter(
            $this->eddingtonCalculator->calculate(),
            static fn (Eddington $eddington): bool => $eddington->getConfig()->showInDashboardWidget()
        );

        if (!$eddingtons) {
            return null;
        }

        return $this->twig->load('html/dashboard/widget/widget--eddington.html.twig')->render([
            'eddingtons' => $eddingtons,
        ]);
    }
}
