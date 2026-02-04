<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use App\Domain\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskTags;
use App\Infrastructure\ValueObject\String\HashtagPrefix;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\String\Tag;
use Money\Currency;
use Money\Money;

final readonly class GearMaintenanceConfig implements \Stringable
{
    private GearComponents $gearComponents;
    private GearOptions $gearOptions;

    private function __construct(
        private bool $isFeatureEnabled,
        private HashtagPrefix $hashtagPrefix,
        private GearMaintenanceCountersResetMode $resetMode,
        private bool $ignoreRetiredGear,
    ) {
        $this->gearComponents = GearComponents::empty();
        $this->gearOptions = GearOptions::empty();
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
                hashtagPrefix: HashtagPrefix::fromString('dummy'),
                resetMode: GearMaintenanceCountersResetMode::NEXT_ACTIVITY_ONWARDS,
                ignoreRetiredGear: false,
            );
        }

        foreach (['enabled', 'hashtagPrefix', 'components', 'gears'] as $requiredKey) {
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

        $resetMode = GearMaintenanceCountersResetMode::NEXT_ACTIVITY_ONWARDS;
        if (!empty($config['countersResetMode'])) {
            $resetMode = GearMaintenanceCountersResetMode::tryFrom($config['countersResetMode']);
            if (is_null($resetMode)) {
                throw new InvalidGearMaintenanceConfig(sprintf('invalid countersResetMode "%s"', $config['countersResetMode']));
            }
        }

        if (array_key_exists('ignoreRetiredGear', $config) && !is_bool($config['ignoreRetiredGear'])) {
            throw new InvalidGearMaintenanceConfig('"ignoreRetiredGear" property must be a boolean');
        }

        $hashtagPrefix = HashtagPrefix::fromString($config['hashtagPrefix']);
        $gearMaintenanceConfig = new self(
            isFeatureEnabled: $config['enabled'],
            hashtagPrefix: $hashtagPrefix,
            resetMode: $resetMode,
            ignoreRetiredGear: !empty($config['ignoreRetiredGear']),
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
            if (empty($component['maintenance'])) {
                throw new InvalidGearMaintenanceConfig(sprintf('No maintenance tasks configured for component "%s"', $component['tag']));
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
                    amount: $component['purchasePrice']['amountInCents'],
                    currency: new Currency($component['purchasePrice']['currency'])
                );
            }
            $gearComponentTag = Tag::fromTags((string) $hashtagPrefix, $component['tag']);
            $gearComponent = GearComponent::create(
                tag: $gearComponentTag,
                label: Name::fromString($component['label']),
                attachedTo: GearIds::fromArray(array_map(
                    GearId::fromUnprefixed(...),
                    $component['attachedTo']
                )),
                imgSrc: $component['imgSrc'] ?? null,
                purchasePrice: $purchasePrice
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
                    tag: Tag::fromTags((string) $gearComponentTag, $task['tag']),
                    label: Name::fromString($task['label']),
                    intervalValue: $task['interval']['value'],
                    intervalUnit: $intervalUnit
                ));
            }

            $maintenanceTags = array_count_values(array_column($component['maintenance'], 'tag'));
            if ($duplicates = array_keys(array_filter($maintenanceTags, fn (int $count): bool => $count > 1))) {
                throw new InvalidGearMaintenanceConfig(sprintf('duplicate maintenance tags found for component "%s:" %s', $gearComponent->getLabel(), implode(', ', $duplicates)));
            }

            $gearMaintenanceConfig->addComponent($gearComponent);
        }

        $componentTags = array_count_values(array_column($config['components'], 'tag'));
        if ($duplicates = array_keys(array_filter($componentTags, fn (int $count): bool => $count > 1))) {
            throw new InvalidGearMaintenanceConfig(sprintf('duplicate component tags found: %s', implode(', ', $duplicates)));
        }

        if (!empty($config['gears']) && !is_array($config['gears'])) {
            throw new InvalidGearMaintenanceConfig('"gears" property must be an array');
        }

        foreach ($config['gears'] ?: [] as $gear) {
            if (empty($gear['gearId'])) {
                throw new InvalidGearMaintenanceConfig('"gearId" property is required for each gear');
            }
            if (empty($gear['imgSrc'])) {
                throw new InvalidGearMaintenanceConfig('"imgSrc" property is required for each gear');
            }
            $gearMaintenanceConfig->addGearOption(
                gearId: GearId::fromUnprefixed($gear['gearId']),
                imgSrc: $gear['imgSrc'],
            );
        }

        return $gearMaintenanceConfig;
    }

    private function addComponent(GearComponent $component): void
    {
        $this->gearComponents->add($component);
    }

    private function addGearOption(GearId $gearId, string $imgSrc): void
    {
        $this->gearOptions->add($gearId, $imgSrc);
    }

    public function getHashtagPrefix(): HashtagPrefix
    {
        return $this->hashtagPrefix;
    }

    public function ignoreRetiredGear(): bool
    {
        return $this->ignoreRetiredGear;
    }

    public function getGearComponents(): GearComponents
    {
        return $this->gearComponents;
    }

    public function getEnrichedGearComponents(MaintenanceTaskTags $maintenanceTaskTags): GearComponents
    {
        $enrichedGearComponents = GearComponents::empty();
        /** @var GearComponent $gearComponent */
        foreach ($this->getGearComponents() as $gearComponent) {
            $enrichedGearComponents->add($gearComponent->withMaintenanceTaskTags($maintenanceTaskTags));
        }

        return $enrichedGearComponents;
    }

    public function getGearOptions(): GearOptions
    {
        return $this->gearOptions;
    }

    public function normalizeGearIds(GearIds $gearIds): void
    {
        /** @var GearComponent $gearComponent */
        foreach ($this->getGearComponents() as $gearComponent) {
            $gearComponent->normalizeGearIds($gearIds);
        }
        $this->getGearOptions()->normalizeGearIds($gearIds);
    }

    /**
     * @return string[]
     */
    public function getAllMaintenanceTags(): array
    {
        return $this->getGearComponents()->getAllMaintenanceTags();
    }

    public function getAllReferencedGearIds(): GearIds
    {
        /** @var GearIds $gearIds */
        $gearIds = $this->getGearComponents()->getAllReferencedGearIds()->mergeWith(
            $this->getGearOptions()->getAllReferencedGearIds()
        )->unique();

        return $gearIds;
    }

    /**
     * @return string[]
     */
    public function getAllReferencedImages(): array
    {
        return array_values(array_unique([
            ...$this->getGearComponents()->getAllReferencedImages(),
            ...$this->getGearOptions()->getAllReferencedImages(),
        ]));
    }

    public function getImageReferenceForGear(GearId $gearId): ?string
    {
        return $this->getGearOptions()->getImageReferenceForGear($gearId);
    }

    public function isFeatureEnabled(): bool
    {
        return $this->isFeatureEnabled;
    }

    public function getResetMode(): GearMaintenanceCountersResetMode
    {
        return $this->resetMode;
    }

    public function __toString(): string
    {
        if (!$this->isFeatureEnabled()) {
            return 'The gear maintenance feature is disabled.';
        }

        $string[] = 'You enabled the gear maintenance feature with the following configuration:';
        $string[] = sprintf('Hashtag prefix: %s', $this->getHashtagPrefix());
        $string[] = sprintf('You added %d components:', count($this->getGearComponents()));
        foreach ($this->getGearComponents() as $gearComponent) {
            $string[] = sprintf('  - Tag: %s', $gearComponent->getTag());
            $string[] = sprintf('    Label: %s', $gearComponent->getLabel());
            $string[] = sprintf('    Attached to: %s', implode(', ', $gearComponent->getAttachedTo()->map(fn (GearId $gearId): string => $gearId->toUnprefixedString())));
            $string[] = sprintf('    Image: %s', $gearComponent->getImgSrc());
            $string[] = '    Maintenance tasks:';
            foreach ($gearComponent->getMaintenanceTasks() as $maintenanceTask) {
                $string[] = sprintf('      - Tag: %s', $maintenanceTask->getTag());
                $string[] = sprintf('        Label: %s', $maintenanceTask->getLabel());
                $string[] = sprintf('        Interval: %d %s', $maintenanceTask->getIntervalValue(), $maintenanceTask->getIntervalUnit()->value);
            }
        }
        if (!$this->getGearOptions()->isEmpty()) {
            $string[] = 'You configured following gear:';
            foreach ($this->getGearOptions()->getOptions() as $gearOption) {
                [$gearId, $imgSrc] = $gearOption;
                $string[] = sprintf('  - Gear ID: %s', $gearId->toUnprefixedString());
                $string[] = sprintf('    Image: %s', $imgSrc);
            }
        }

        return implode(PHP_EOL, $string);
    }
}
