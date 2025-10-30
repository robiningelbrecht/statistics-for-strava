<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\WeeklyGoals;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<WeeklyGoal>
 */
final class WeeklyGoals extends Collection
{
    public function getItemClassName(): string
    {
        return WeeklyGoal::class;
    }

    /**
     * @param array<int, mixed> $items
     */
    public static function fromConfig(array $items): self
    {
        if (empty($items)) {
            return self::empty();
        }

        $weeklyGoals = [];
        foreach ($items as $goalConfig) {
            if (!is_array($goalConfig)) {
                throw new InvalidWeeklyGoalsConfiguration('Invalid goals configuration provided');
            }

            foreach (['label', 'enabled', 'type', 'unit', 'goal', 'sportTypesToInclude'] as $requiredKey) {
                if (array_key_exists($requiredKey, $goalConfig)) {
                    continue;
                }
                throw new InvalidWeeklyGoalsConfiguration(sprintf('"%s" property is required', $requiredKey));
            }

            if (empty($goalConfig['label'])) {
                throw new InvalidWeeklyGoalsConfiguration('"label" property cannot be empty');
            }

            if (!is_bool($goalConfig['enabled'])) {
                throw new InvalidWeeklyGoalsConfiguration('"enabled" property must be a boolean');
            }

            if (!is_numeric($goalConfig['goal'])) {
                throw new InvalidWeeklyGoalsConfiguration('"goal" property must be a valid number');
            }

            if (!$type = WeeklyGoalType::tryFrom($goalConfig['type'])) {
                throw new InvalidWeeklyGoalsConfiguration(sprintf('"%s" is not a valid type', $goalConfig['type']));
            }

            if (!is_array($goalConfig['sportTypesToInclude'])) {
                throw new InvalidWeeklyGoalsConfiguration('"sportTypesToInclude" property must be an array');
            }

            if (empty($goalConfig['sportTypesToInclude'])) {
                throw new InvalidWeeklyGoalsConfiguration('"sportTypesToInclude" property cannot be empty');
            }

            $sportTypesToInclude = SportTypes::empty();
            foreach ($goalConfig['sportTypesToInclude'] as $sportTypeToInclude) {
                if (!$sportType = SportType::tryFrom($sportTypeToInclude)) {
                    throw new InvalidWeeklyGoalsConfiguration(sprintf('"%s" is not a valid sport type', $sportTypeToInclude));
                }
                $sportTypesToInclude->add($sportType);
            }

            if (in_array($type, WeeklyGoalType::lengthRelated()) && !in_array($goalConfig['unit'], [
                WeeklyGoal::KILOMETER,
                WeeklyGoal::METER,
                WeeklyGoal::MILES,
                WeeklyGoal::FOOT,
            ])) {
                throw new InvalidWeeklyGoalsConfiguration(sprintf('The unit "%s" is not valid for goal type "%s"', $goalConfig['unit'], $type->value));
            }

            if (WeeklyGoalType::MOVING_TIME === $type && !in_array($goalConfig['unit'], [
                WeeklyGoal::HOUR,
                WeeklyGoal::MINUTE,
            ])) {
                throw new InvalidWeeklyGoalsConfiguration(sprintf('The unit "%s" is not valid for goal type "%s"', $goalConfig['unit'], $type->value));
            }

            $weeklyGoals[] = WeeklyGoal::create(
                label: $goalConfig['label'],
                isEnabled: $goalConfig['enabled'],
                type: $type,
                goal: (float) $goalConfig['goal'],
                unit: $goalConfig['unit'],
                sportTypesToInclude: $sportTypesToInclude
            );
        }

        return self::fromArray($weeklyGoals);
    }
}
