<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

use App\Domain\Calendar\Month;
use App\Domain\Calendar\Week;
use App\Domain\Dashboard\Widget\TrainingGoals\FindTrainingGoalMetrics\FindTrainingGoalMetrics;
use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
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
        /** @var array<string, mixed> $config */
        $config = $configuration->get('goals');
        TrainingGoals::fromConfig($config);
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        if (empty($configuration->get('goals'))) {
            return null;
        }

        /** @var non-empty-array<string, mixed> $config */
        $config = $configuration->get('goals');
        $trainingGoals = TrainingGoals::fromConfig($config);

        if ($trainingGoals->isEmpty()) {
            return null;
        }

        $trainingGoalsPerPeriod = [];
        foreach ($trainingGoals as $trainingGoal) {
            $trainingGoalsPerPeriod[$trainingGoal->getPeriod()->value][] = $trainingGoal;
        }

        $calculatedGoalsPerPeriod = $fromToLabels = [];
        $week = Week::fromYearAndWeekNumber($now->getYear(), $now->getWeekNumber());
        $month = Month::fromDate($now);
        $year = Year::fromDate($now);

        foreach ($trainingGoalsPerPeriod as $period => $trainingGoals) {
            [$from, $to] = match (TrainingGoalPeriod::from($period)) {
                TrainingGoalPeriod::WEEKLY => [$week->getFrom(), $week->getTo()],
                TrainingGoalPeriod::MONTHLY => [$month->getFrom(), $month->getTo()],
                TrainingGoalPeriod::YEARLY => [$year->getFrom(), $year->getTo()],
                TrainingGoalPeriod::LIFETIME => [SerializableDateTime::fromString('01-01-1970'), $now],
            };

            $fromToLabels[$period] = match (TrainingGoalPeriod::from($period)) {
                TrainingGoalPeriod::WEEKLY,
                TrainingGoalPeriod::MONTHLY => implode(' - ', [
                    $from->translatedFormat('d M'),
                    $to->translatedFormat('d M'),
                ]),
                TrainingGoalPeriod::YEARLY => implode(' - ', [
                    $from->translatedFormat('d M Y'),
                    $to->translatedFormat('d M Y'),
                ]),
                TrainingGoalPeriod::LIFETIME => null,
            };

            foreach ($trainingGoals as $trainingGoal) {
                if (!$trainingGoal->isEnabled()) {
                    continue;
                }

                $response = $this->queryBus->ask(new FindTrainingGoalMetrics(
                    sportTypes: $trainingGoal->getSportTypesToInclude(),
                    from: $from,
                    to: $to,
                ));

                $convertedProgress = match ($trainingGoal->getType()) {
                    TrainingGoalType::DISTANCE => $trainingGoal->convertKilometerToGoalUnit($response->getDistance()),
                    TrainingGoalType::ELEVATION => $trainingGoal->convertMeterToGoalUnit($response->getElevation()),
                    TrainingGoalType::MOVING_TIME => $trainingGoal->convertSecondsToGoalUnit($response->getMovingTime()),
                };

                $calculatedGoalsPerPeriod[$period][] = [
                    'trainingGoal' => $trainingGoal,
                    'absolute' => $convertedProgress,
                    'relative' => min(100, round($convertedProgress->toFloat() / $trainingGoal->getGoal()->toFloat() * 100)),
                ];
            }
        }

        return $this->twig->load('html/dashboard/widget/widget--training-goals.html.twig')->render([
            'fromToLabels' => $fromToLabels,
            'calculatedTrainingGoalsPerPeriod' => $calculatedGoalsPerPeriod,
        ]);
    }
}
