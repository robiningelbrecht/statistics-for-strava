<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\DeleteGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteGearMaintenanceComponentCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteGearMaintenanceComponent);

        $this->gearMaintenanceRepository->deleteComponent($command->getGearComponentId());
    }
}
