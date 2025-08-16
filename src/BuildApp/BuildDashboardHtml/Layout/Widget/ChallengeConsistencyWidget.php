<?php

declare(strict_types=1);

namespace App\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Calendar\Months;
use App\Domain\Challenge\Consistency\ConsistencyChallengeCalculator;
use App\Domain\Challenge\Consistency\ConsistencyChallenges;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class ChallengeConsistencyWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private ConsistencyChallenges $consistencyChallenges,
        private ConsistencyChallengeCalculator $consistencyChallengeCalculator,
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

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        return $this->twig->load('html/dashboard/widget/widget--challenge-consistency.html.twig')->render([
            'allMonths' => $allMonths,
            'allConsistencyChallenges' => $this->consistencyChallenges,
            'calculatedConsistencyChallenges' => $this->consistencyChallengeCalculator->calculateFor(
                months: $allMonths,
                challenges: $this->consistencyChallenges
            ),
        ]);
    }
}
