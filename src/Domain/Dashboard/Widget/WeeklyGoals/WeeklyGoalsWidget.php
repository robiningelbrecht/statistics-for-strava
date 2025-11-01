<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeeklyGoals;

use App\Domain\Calendar\Week;
use App\Domain\Dashboard\Widget\WeeklyGoals\FindWeeklyGoalMetrics\FindWeeklyGoalMetrics;
use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class WeeklyGoalsWidget implements Widget
{
    public function __construct(
        private QueryBus $queryBus,
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

        $week = Week::fromYearAndWeekNumber($now->getYear(), $now->getWeekNumber());
        /** @var non-empty-array<int, mixed> $config */
        $config = $configuration->get('goals');
        $weeklyGoals = WeeklyGoals::fromConfig($config);

        $calculatedGoals = [];
        foreach ($weeklyGoals as $weeklyGoal) {
            if (!$weeklyGoal->isEnabled()) {
                continue;
            }

            $response = $this->queryBus->ask(new FindWeeklyGoalMetrics(
                sportTypes: $weeklyGoal->getSportTypesToInclude(),
                week: $week
            ));

            $convertedGoal = match ($weeklyGoal->getType()) {
                WeeklyGoalType::DISTANCE => $weeklyGoal->convertKilometerToGoalUnit($response->getDistance()),
                WeeklyGoalType::ELEVATION => $weeklyGoal->convertMeterToGoalUnit($response->getElevation()),
                WeeklyGoalType::MOVING_TIME => $weeklyGoal->convertSecondsToGoalUnit($response->getMovingTime()),
            };

            $calculatedGoals[] = [
                'weeklyGoal' => $weeklyGoal,
                'absolute' => $convertedGoal,
                'relative' => min(100, round($convertedGoal->toFloat() / $weeklyGoal->getGoal()->toFloat() * 100)),
            ];
        }

        return $this->twig->load('html/dashboard/widget/widget--weekly-goals.html.twig')->render([
            'fromToLabel' => $week->getLabelFromTo(),
            'calculatedWeeklyGoals' => $calculatedGoals,
        ]);
    }
}
