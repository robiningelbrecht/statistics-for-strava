<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\UpdateGearMaintenanceSettings;

use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\DomainCommand;

#[AsDeserializableCommand(UpdateGearMaintenanceSettings::NAME)]
final readonly class UpdateGearMaintenanceSettings extends DomainCommand implements DeserializableCommand
{
    public const string NAME = 'update-gear-maintenance-settings';

    private function __construct(
        private bool $isFeatureEnabled,
        private bool $ignoreRetiredGear,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            isFeatureEnabled: filter_var($payload['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ignoreRetiredGear: filter_var($payload['ignoreRetiredGear'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    public function isFeatureEnabled(): bool
    {
        return $this->isFeatureEnabled;
    }

    public function ignoreRetiredGear(): bool
    {
        return $this->ignoreRetiredGear;
    }
}
