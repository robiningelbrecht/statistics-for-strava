<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

use App\Domain\Calendar\Week;
use App\Domain\Dashboard\Widget\TrainingGoals\FindTrainingGoalMetrics\FindTrainingGoalMetrics;
use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class TrainingGoalsWidget implements Widget
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
        TrainingGoals::fromConfig($config);
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        if (empty($configuration->get('goals'))) {
            return null;
        }

        $week = Week::fromYearAndWeekNumber($now->getYear(), $now->getWeekNumber());
        /** @var non-empty-array<int, mixed> $config */
        $config = $configuration->get('goals');
        $trainingGoals = TrainingGoals::fromConfig($config);

        $calculatedGoals = [];
        foreach ($trainingGoals as $trainingGoal) {
            if (!$trainingGoal->isEnabled()) {
                continue;
            }

            $response = $this->queryBus->ask(new FindTrainingGoalMetrics(
                sportTypes: $trainingGoal->getSportTypesToInclude(),
                week: $week
            ));

            $convertedProgress = match ($trainingGoal->getType()) {
                TrainingGoalType::DISTANCE => $trainingGoal->convertKilometerToGoalUnit($response->getDistance()),
                TrainingGoalType::ELEVATION => $trainingGoal->convertMeterToGoalUnit($response->getElevation()),
                TrainingGoalType::MOVING_TIME => $trainingGoal->convertSecondsToGoalUnit($response->getMovingTime()),
            };

            $calculatedGoals[] = [
                'trainingGoal' => $trainingGoal,
                'absolute' => $convertedProgress,
                'relative' => min(100, round($convertedProgress->toFloat() / $trainingGoal->getGoal()->toFloat() * 100)),
            ];
        }

        return $this->twig->load('html/dashboard/widget/widget--training-goals.html.twig')->render([
            'fromToLabel' => $week->getLabelFromTo(),
            'calculatedTrainingGoals' => $calculatedGoals,
        ]);
    }
}
