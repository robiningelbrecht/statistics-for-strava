<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<TrainingGoal>
 */
final class TrainingGoals extends Collection
{
    public function getItemClassName(): string
    {
        return TrainingGoal::class;
    }

    /**
     * @param array<string, mixed> $items
     */
    public static function fromConfig(array $items): self
    {
        if (empty($items)) {
            return self::empty();
        }

        $trainingGoals = [];

        foreach (array_keys($items) as $trainingGoalPeriod) {
            if (TrainingGoalPeriod::tryFrom($trainingGoalPeriod)) {
                continue;
            }

            throw new InvalidTrainingGoalsConfiguration(sprintf('"%s" is not a valid goal period', $trainingGoalPeriod));
        }

        foreach ($items as $period => $periodGoalConfig) {
            $trainingGoalPeriod = TrainingGoalPeriod::from($period);
            foreach ($periodGoalConfig as $goalConfig) {
                if (!is_array($goalConfig)) {
                    throw new InvalidTrainingGoalsConfiguration('Invalid TrainingGoals configuration provided');
                }

                foreach (['label', 'enabled', 'type', 'unit', 'goal', 'sportTypesToInclude'] as $requiredKey) {
                    if (array_key_exists($requiredKey, $goalConfig)) {
                        continue;
                    }
                    throw new InvalidTrainingGoalsConfiguration(sprintf('"%s" property is required', $requiredKey));
                }

                if (empty($goalConfig['label'])) {
                    throw new InvalidTrainingGoalsConfiguration('"label" property cannot be empty');
                }

                if (!is_bool($goalConfig['enabled'])) {
                    throw new InvalidTrainingGoalsConfiguration('"enabled" property must be a boolean');
                }

                if (!is_numeric($goalConfig['goal'])) {
                    throw new InvalidTrainingGoalsConfiguration('"goal" property must be a valid number');
                }

                if (!$type = TrainingGoalType::tryFrom($goalConfig['type'])) {
                    throw new InvalidTrainingGoalsConfiguration(sprintf('"%s" is not a valid goalType', $goalConfig['type']));
                }

                if (!is_array($goalConfig['sportTypesToInclude'])) {
                    throw new InvalidTrainingGoalsConfiguration('"sportTypesToInclude" property must be an array');
                }

                if (empty($goalConfig['sportTypesToInclude'])) {
                    throw new InvalidTrainingGoalsConfiguration('"sportTypesToInclude" property cannot be empty');
                }

                $sportTypesToInclude = SportTypes::empty();
                foreach ($goalConfig['sportTypesToInclude'] as $sportTypeToInclude) {
                    if (!$sportType = SportType::tryFrom($sportTypeToInclude)) {
                        throw new InvalidTrainingGoalsConfiguration(sprintf('"%s" is not a valid sport type', $sportTypeToInclude));
                    }
                    $sportTypesToInclude->add($sportType);
                }

                if (in_array($type, TrainingGoalType::lengthRelated()) && !in_array($goalConfig['unit'], [
                    TrainingGoal::KILOMETER,
                    TrainingGoal::METER,
                    TrainingGoal::MILES,
                    TrainingGoal::FOOT,
                ])) {
                    throw new InvalidTrainingGoalsConfiguration(sprintf('The unit "%s" is not valid for goal type "%s"', $goalConfig['unit'], $type->value));
                }

                if (TrainingGoalType::MOVING_TIME === $type && !in_array($goalConfig['unit'], [
                    TrainingGoal::HOUR,
                    TrainingGoal::MINUTE,
                ])) {
                    throw new InvalidTrainingGoalsConfiguration(sprintf('The unit "%s" is not valid for goal type "%s"', $goalConfig['unit'], $type->value));
                }

                if (in_array($type, TrainingGoalType::simpleUnitRelated())) {
                    // Hardcode the unit.
                    $goalConfig['unit'] = TrainingGoal::SIMPLE;
                }

                $trainingGoals[] = TrainingGoal::create(
                    label: $goalConfig['label'],
                    isEnabled: $goalConfig['enabled'],
                    type: $type,
                    period: $trainingGoalPeriod,
                    goal: (float) $goalConfig['goal'],
                    unit: $goalConfig['unit'],
                    sportTypesToInclude: $sportTypesToInclude
                );
            }
        }

        return self::fromArray($trainingGoals);
    }
}
