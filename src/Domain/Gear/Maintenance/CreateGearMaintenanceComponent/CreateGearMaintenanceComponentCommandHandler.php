<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\CreateGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\GearComponent;
use App\Domain\Gear\Maintenance\GearComponentId;
use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class CreateGearMaintenanceComponentCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CreateGearMaintenanceComponent);

        $gearComponent = GearComponent::create(
            id: GearComponentId::random(),
            label: $command->getLabel(),
            attachedTo: $command->getAttachedTo(),
            imgSrc: $command->getImgSrc(),
            purchasePrice: $command->getPurchasePrice(),
        );

        foreach ($command->getMaintenanceTasks() as $maintenanceTask) {
            $gearComponent->addMaintenanceTask($maintenanceTask);
        }

        $this->gearMaintenanceRepository->saveComponent($gearComponent);
    }
}
