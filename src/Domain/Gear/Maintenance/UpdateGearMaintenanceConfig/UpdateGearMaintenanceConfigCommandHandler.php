<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\UpdateGearMaintenanceConfig;

use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class UpdateGearMaintenanceConfigCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateGearMaintenanceConfig);

        $this->gearMaintenanceRepository->updateConfig(
            isFeatureEnabled: $command->isFeatureEnabled(),
            ignoreRetiredGear: $command->ignoreRetiredGear(),
        );
    }
}
