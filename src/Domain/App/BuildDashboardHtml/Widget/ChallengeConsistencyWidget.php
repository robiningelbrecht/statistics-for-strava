<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Widget;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallengeCalculator;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallenges;
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

    public function render(SerializableDateTime $now): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        return $this->twig->load('html/dashboard/widget/challenge-consistency.html.twig')->render([
            'allMonths' => $allMonths,
            'allConsistencyChallenges' => $this->consistencyChallenges,
            'calculatedConsistencyChallenges' => $this->consistencyChallengeCalculator->calculateFor(
                months: $allMonths,
                challenges: $this->consistencyChallenges
            ),
        ]);
    }
}
