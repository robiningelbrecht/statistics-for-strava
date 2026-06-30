<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\CreateGearMaintenanceComponent;

use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\ParsesGearMaintenanceComponentPayload;
use App\Domain\Gear\Maintenance\Task\MaintenanceTasks;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\ProvidesCommandName;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\String\Name;
use Money\Money;

final readonly class CreateGearMaintenanceComponent extends DomainCommand implements DeserializableCommand
{
    use ProvidesCommandName;
    use ParsesGearMaintenanceComponentPayload;

    private function __construct(
        private Name $label,
        private GearIds $attachedTo,
        private ?string $imgSrc,
        private ?Money $purchasePrice,
        private MaintenanceTasks $maintenanceTasks,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            label: self::parseLabel($payload),
            attachedTo: self::parseAttachedTo($payload),
            imgSrc: self::parseImgSrc($payload),
            purchasePrice: self::parsePurchasePrice($payload),
            maintenanceTasks: self::parseMaintenanceTasks($payload, generateMissingIds: true),
        );
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
