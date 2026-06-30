<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\UpdateGearMaintenanceComponent;

use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\GearComponentId;
use App\Domain\Gear\Maintenance\ParsesGearMaintenanceComponentPayload;
use App\Domain\Gear\Maintenance\Task\MaintenanceTasks;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\ProvidesCommandName;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\String\Name;
use Money\Money;

final readonly class UpdateGearMaintenanceComponent extends DomainCommand implements DeserializableCommand
{
    use ProvidesCommandName;
    use ParsesGearMaintenanceComponentPayload;

    private function __construct(
        private GearComponentId $gearComponentId,
        private Name $label,
        private GearIds $attachedTo,
        private ?string $imgSrc,
        private ?Money $purchasePrice,
        private MaintenanceTasks $maintenanceTasks,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        if (!isset($payload['gearComponentId']) || !is_string($payload['gearComponentId']) || '' === trim($payload['gearComponentId'])) {
            throw CouldNotDeserializeCommand::invalidPayload('A "gearComponentId" is required.');
        }

        return new self(
            gearComponentId: GearComponentId::fromString(trim($payload['gearComponentId'])),
            label: self::parseLabel($payload),
            attachedTo: self::parseAttachedTo($payload),
            imgSrc: self::parseImgSrc($payload),
            purchasePrice: self::parsePurchasePrice($payload),
            maintenanceTasks: self::parseMaintenanceTasks($payload, generateMissingIds: true),
        );
    }

    public function getGearComponentId(): GearComponentId
    {
        return $this->gearComponentId;
    }

    public function getLabel(): Name
    {
        return $this->label;
    }

    public function getAttachedTo(): GearIds
    {
        return $this->attachedTo;
    }

    public function getImgSrc(): ?string
    {
        return $this->imgSrc;
    }

    public function getPurchasePrice(): ?Money
    {
        return $this->purchasePrice;
    }

    public function getMaintenanceTasks(): MaintenanceTasks
    {
        return $this->maintenanceTasks;
    }
}
