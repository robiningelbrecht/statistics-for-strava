<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Infrastructure\ValueObject\String\Name;
use Symfony\Component\Yaml\Yaml;

final readonly class GearMaintenanceConfig
{
    private GearComponents $gearComponents;

    private function __construct(
        private bool $isFeatureEnabled,
        private HashtagPrefix $hashtagPrefix,
    ) {
        $this->gearComponents = GearComponents::empty();
    }

    public static function fromYmlString(
        ?string $ymlContent,
    ): self {
        if (is_null($ymlContent)) {
            return new self(
                isFeatureEnabled: false,
                hashtagPrefix: HashtagPrefix::fromString('dummy-'),
            );
        }
        $config = Yaml::parse($ymlContent);

        foreach (['enabled', 'hashtagPrefix', 'components'] as $requiredKey) {
            if (array_key_exists($requiredKey, $config)) {
                continue;
            }
            throw new InvalidGearMaintenanceConfig(sprintf('"%s" property is required', $requiredKey));
        }

        if (!is_array($config['components'])) {
            throw new InvalidGearMaintenanceConfig('"components" property must be an array');
        }

        $gearMaintenanceConfig = new self(
            isFeatureEnabled: $config['enabled'],
            hashtagPrefix: HashtagPrefix::fromString($config['hashtagPrefix']),
        );

        foreach ($config['components'] as $component) {
            foreach (['tag', 'label', 'attachedTo', 'maintenance'] as $requiredKey) {
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

            $gearComponent = GearComponent::create(
                tag: Tag::fromString($component['tag']),
                label: Name::fromString($component['label']),
                attachedTo: GearIds::fromArray(array_map(
                    fn (string $gearId) => GearId::fromString($gearId),
                    $component['attachedTo']
                )),
            );

            foreach ($component['maintenance'] as $task) {
                foreach (['tag', 'label', 'interval'] as $requiredKey) {
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
                    tag: Tag::fromString($task['tag']),
                    label: Name::fromString($task['label']),
                    intervalValue: $task['interval']['value'],
                    intervalUnit: $intervalUnit
                ));
            }

            $maintenanceTags = array_count_values(array_column($component['maintenance'], 'tag'));
            if ($duplicates = array_keys(array_filter($maintenanceTags, fn (int $count) => $count > 1))) {
                throw new InvalidGearMaintenanceConfig(sprintf('duplicate maintenance tags found for component "%s:" %s', $gearComponent->getLabel(), implode(', ', $duplicates)));
            }

            $gearMaintenanceConfig->addComponent($gearComponent);
        }

        $componentTags = array_count_values(array_column($config['components'], 'tag'));
        if ($duplicates = array_keys(array_filter($componentTags, fn (int $count) => $count > 1))) {
            throw new InvalidGearMaintenanceConfig(sprintf('duplicate component tags found: %s', implode(', ', $duplicates)));
        }

        return $gearMaintenanceConfig;
    }

    private function addComponent(GearComponent $component): void
    {
        $this->gearComponents->add($component);
    }

    public function getHashtagPrefix(): HashtagPrefix
    {
        return $this->hashtagPrefix;
    }

    public function getGearComponents(): GearComponents
    {
        return $this->gearComponents;
    }

    public function isFeatureEnabled(): bool
    {
        return $this->isFeatureEnabled;
    }
}
