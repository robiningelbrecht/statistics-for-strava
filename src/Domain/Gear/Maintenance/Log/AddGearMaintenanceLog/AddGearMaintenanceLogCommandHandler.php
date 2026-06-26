<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log\AddGearMaintenanceLog;

use App\Domain\Gear\Maintenance\Log\GearMaintenanceLog;
use App\Domain\Gear\Maintenance\Log\GearMaintenanceLogRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class AddGearMaintenanceLogCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceLogRepository $gearMaintenanceLogRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof AddGearMaintenanceLog);

        $this->gearMaintenanceLogRepository->add(GearMaintenanceLog::create(
            gearId: $command->getGearId(),
            maintenanceTaskId: $command->getMaintenanceTaskId(),
            performedOn: $command->getPerformedOn(),
        ));
    }
}
