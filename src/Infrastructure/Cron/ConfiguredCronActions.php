<?php

declare(strict_types=1);

namespace App\Infrastructure\Cron;

use App\Domain\Gear\Maintenance\InvalidGearMaintenanceConfig;
use App\Infrastructure\ValueObject\Collection;
use Cron\CronExpression;

/**
 * @extends Collection<ConfiguredCronAction>
 */
class ConfiguredCronActions extends Collection
{
    public function getItemClassName(): string
    {
        return ConfiguredCronAction::class;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromConfig(array $data): self
    {
        $configuredCronActions = [];
        foreach ($data as $configuredCronAction) {
            if (!is_array($configuredCronAction)) {
                throw new InvalidCronConfig('each configured cron item needs to be an array');
            }
            foreach (['id', 'cronExpression'] as $requiredKey) {
                if (!empty($configuredCronAction[$requiredKey])) {
                    continue;
                }
                throw new InvalidCronConfig(sprintf('"%s" property is required', $requiredKey));
            }

            if (!CronExpression::isValidExpression($configuredCronAction['cronExpression'])) {
                throw new InvalidCronConfig(sprintf('"%s" is not a valid cron expression', $configuredCronAction['cronExpression']));
            }

            $configuredCronActions[] = ConfiguredCronAction::create(
                cronActionId: $configuredCronAction['id'],
                cronExpression: new CronExpression($configuredCronAction['cronExpression']),
            );
        }

        $cronActionIds = array_count_values(array_column($configuredCronActions, 'id'));
        if (array_keys(array_filter($cronActionIds, fn (int $count): bool => $count > 1))) {
            throw new InvalidGearMaintenanceConfig('each cron jon can only be configured once');
        }

        return self::fromArray($configuredCronActions);
    }
}
