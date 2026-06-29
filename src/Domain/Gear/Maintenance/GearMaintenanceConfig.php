<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use App\Domain\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\ValueObject\String\Name;
use Money\Currency;
use Money\Money;

final readonly class GearMaintenanceConfig
{
    private GearComponents $gearComponents;

    private function __construct(
        private bool $isFeatureEnabled,
        private bool $ignoreRetiredGear,
    ) {
        $this->gearComponents = GearComponents::empty();
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function fromArray(
        ?array $config,
    ): self {
        if (null === $config || [] === $config) {
            return new self(
                isFeatureEnabled: false,
                ignoreRetiredGear: false,
            );
        }

        foreach (['enabled', 'components'] as $requiredKey) {
            if (array_key_exists($requiredKey, $config)) {
                continue;
            }
            throw new InvalidGearMaintenanceConfig(sprintf('"%s" property is required', $requiredKey));
        }

        if (!is_array($config['components'])) {
            throw new InvalidGearMaintenanceConfig('"components" property must be an array');
        }

        if (empty($config['components'])) {
            throw new InvalidGearMaintenanceConfig('You must configure at least one component');
        }

        if (array_key_exists('ignoreRetiredGear', $config) && !is_bool($config['ignoreRetiredGear'])) {
            throw new InvalidGearMaintenanceConfig('"ignoreRetiredGear" property must be a boolean');
        }

        $gearMaintenanceConfig = new self(
            isFeatureEnabled: $config['enabled'],
            ignoreRetiredGear: !empty($config['ignoreRetiredGear']),
        );

        foreach ($config['components'] as $component) {
            foreach (['id', 'label', 'attachedTo', 'maintenance'] as $requiredKey) {
                if (array_key_exists($requiredKey, $component)) {
                    continue;
                }
                throw new InvalidGearMaintenanceConfig(sprintf('"%s" property is required for each component', $requiredKey));
            }

            if (!is_array($component['attachedTo'])) {
                throw new InvalidGearMaintenanceConfig('"attachedTo" property must be an array');
            }
            if (!is_array($component['maintenance'])) {
                throw new InvalidGearMaintenanceConfig('"maintenance" property must be an array');
            }
            if (empty($component['maintenance'])) {
                throw new InvalidGearMaintenanceConfig(sprintf('No maintenance tasks configured for component "%s"', $component['id']));
            }
            if (!is_null($component['imgSrc']) && !is_string($component['imgSrc'])) {
                throw new InvalidGearMaintenanceConfig('"imgSrc" property must be a string');
            }
            if (isset($component['purchasePrice']) && empty($component['purchasePrice']['amountInCents'])) {
                throw new InvalidGearMaintenanceConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($component['purchasePrice']) && !is_numeric($component['purchasePrice']['amountInCents'])) {
                throw new InvalidGearMaintenanceConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($component['purchasePrice']) && empty($component['purchasePrice']['currency'])) {
                throw new InvalidGearMaintenanceConfig('"purchasePrice.currency" property is required');
            }

            $purchasePrice = null;
            if (!empty($component['purchasePrice']['amountInCents'])) {
                $purchasePrice = new Money(
                    amount: (int) $component['purchasePrice']['amountInCents'],
                    currency: new Currency($component['purchasePrice']['currency'])
                );
            }
            $gearComponent = GearComponent::create(
                label: Name::fromString($component['label']),
                attachedTo: GearIds::fromArray(array_map(
                    GearId::fromUnprefixed(...),
                    $component['attachedTo']
                )),
                imgSrc: $component['imgSrc'] ?? null,
                purchasePrice: $purchasePrice
            );

            foreach ($component['maintenance'] as $task) {
                foreach (['id', 'label', 'interval'] as $requiredKey) {
                    if (array_key_exists($requiredKey, $task)) {
                        continue;
                    }
                    throw new InvalidGearMaintenanceConfig(sprintf('"%s" property is required for each maintenance task', $requiredKey));
                }
                if (empty($task['interval']['value']) || empty($task['interval']['unit'])) {
                    throw new InvalidGearMaintenanceConfig('"interval" property must have "value" and "unit" properties');
                }

                if (!$intervalUnit = IntervalUnit::tryFrom($task['interval']['unit'])) {
                    throw new InvalidGearMaintenanceConfig(sprintf('invalid interval unit "%s"', $task['interval']['unit']));
                }

                $gearComponent->addMaintenanceTask(MaintenanceTask::create(
                    id: MaintenanceTaskId::fromUnprefixed($task['id']),
                    label: Name::fromString($task['label']),
                    intervalValue: $task['interval']['value'],
                    intervalUnit: $intervalUnit
                ));
            }

            $maintenanceTaskIds = array_count_values(array_column($component['maintenance'], 'id'));
            if ($duplicates = array_keys(array_filter($maintenanceTaskIds, fn (int $count): bool => $count > 1))) {
                throw new InvalidGearMaintenanceConfig(sprintf('duplicate maintenance task ids found for component "%s:" %s', $gearComponent->getLabel(), implode(', ', $duplicates)));
            }

            $gearMaintenanceConfig->addComponent($gearComponent);
        }

        $componentIds = array_count_values(array_column($config['components'], 'id'));
        if ($duplicates = array_keys(array_filter($componentIds, fn (int $count): bool => $count > 1))) {
            throw new InvalidGearMaintenanceConfig(sprintf('duplicate component ids found: %s', implode(', ', $duplicates)));
        }

        return $gearMaintenanceConfig;
    }

    private function addComponent(GearComponent $component): void
    {
        $this->gearComponents->add($component);
    }

    public function ignoreRetiredGear(): bool
    {
        return $this->ignoreRetiredGear;
    }

    public function getGearComponents(): GearComponents
    {
        return $this->gearComponents;
    }

    public function normalizeGearIds(GearIds $gearIds): void
    {
        foreach ($this->getGearComponents() as $gearComponent) {
            $gearComponent->normalizeGearIds($gearIds);
        }
    }

    public function getAllReferencedGearIds(): GearIds
    {
        return $this->getGearComponents()->getAllReferencedGearIds()->unique();
    }

    public function isFeatureEnabled(): bool
    {
        return $this->isFeatureEnabled;
    }
}
