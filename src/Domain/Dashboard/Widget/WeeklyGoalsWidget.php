<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Calendar\Week;
use App\Domain\Dashboard\WeeklyGoals\WeeklyGoals;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class WeeklyGoalsWidget implements Widget
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('goals', []);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        /** @var array<int, mixed> $config */
        $config = $configuration->get('goals');
        WeeklyGoals::fromConfig($config);
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        if (empty($configuration->get('goals'))) {
            return null;
        }

        return $this->twig->load('html/dashboard/widget/widget--weekly-goals.html.twig')->render([
            'fromToLabel' => Week::fromYearAndWeekNumber($now->getYear(), $now->getWeekNumber())->getLabelFromTo(),
            'calculatedWeeklyGoals' => [
                1, 2, 3,
            ],
        ]);
    }
}
