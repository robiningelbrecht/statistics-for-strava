<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log\UpdateGearMaintenanceLog;

use App\Domain\Gear\Maintenance\Log\GearMaintenanceLogRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class UpdateGearMaintenanceLogCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceLogRepository $gearMaintenanceLogRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateGearMaintenanceLog);

        $gearMaintenanceLog = $this->gearMaintenanceLogRepository
            ->find($command->getGearMaintenanceLogId())
            ->withPerformedOn($command->getPerformedOn());

        $this->gearMaintenanceLogRepository->update($gearMaintenanceLog);
    }
}
