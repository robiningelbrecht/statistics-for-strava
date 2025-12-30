<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Rewind\FindStreaks\FindStreaks;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Twig\Environment;

final readonly class StreaksWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private QueryBus $queryBus,
        private Clock $clock,
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('sportTypesToInclude', []);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (empty($configuration->get('sportTypesToInclude'))) {
            return;
        }

        if (!is_array($configuration->get('sportTypesToInclude'))) {
            throw new InvalidDashboardLayout('Configuration item "sportTypesToInclude" must be an array for StreaksWidget.');
        }

        foreach ($configuration->get('sportTypesToInclude') as $sportTypeToInclude) {
            if (!SportType::tryFrom($sportTypeToInclude)) {
                throw new InvalidDashboardLayout(sprintf('"%s" is not a valid sport type', $sportTypeToInclude));
            }
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        /** @var string[] $sportTypesToInclude */
        $sportTypesToInclude = $configuration->get('sportTypesToInclude');
        $findStreaksResponse = $this->queryBus->ask(new FindStreaks(
            years: Years::all($this->clock->getCurrentDateTimeImmutable()),
            restrictToSportTypes: SportTypes::fromArray(array_map(
                SportType::from(...),
                $sportTypesToInclude,
            )),
        ));

        return $this->twig->load('html/dashboard/widget/widget--streaks.html.twig')->render([
            'mostRecentActivity' => $this->activitiesEnricher->getEnrichedActivities()->getFirst(),
            'dayStreak' => $findStreaksResponse->getCurrentDayStreak(),
            'weekStreak' => $findStreaksResponse->getCurrentWeekStreak(),
            'monthStreak' => $findStreaksResponse->getCurrentMonthStreak(),
        ]);
    }
}
