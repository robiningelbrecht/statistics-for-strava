<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class MostRecentActivitiesWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('numberOfActivitiesToDisplay', 5);
    }

    public function guardValidConfiguration(array $config): void
    {
        if (!array_key_exists('numberOfActivitiesToDisplay', $config)) {
            throw new InvalidDashboardLayout('Configuration item "numberOfActivitiesToDisplay" is required for MostRecentActivitiesWidget.');
        }

        if (!is_int($config['numberOfActivitiesToDisplay'])) {
            throw new InvalidDashboardLayout('Configuration item "numberOfActivitiesToDisplay" must be an integer.');
        }

        if ($config['numberOfActivitiesToDisplay'] < 1) {
            throw new InvalidDashboardLayout('Configuration item "numberOfActivitiesToDisplay" must be set to a value of 1 or greater.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();

        /** @var int $numberOfActivitiesToDisplay */
        $numberOfActivitiesToDisplay = $configuration->getConfigItem('numberOfActivitiesToDisplay');

        return $this->twig->load('html/dashboard/widget/widget--most-recent-activities.html.twig')->render([
            'mostRecentActivities' => $allActivities->slice(0, $numberOfActivitiesToDisplay),
        ]);
    }
}
