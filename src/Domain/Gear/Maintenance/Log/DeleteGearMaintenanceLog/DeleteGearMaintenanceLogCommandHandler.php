<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log\DeleteGearMaintenanceLog;

use App\Domain\Gear\Maintenance\Log\GearMaintenanceLogRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteGearMaintenanceLogCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceLogRepository $gearMaintenanceLogRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteGearMaintenanceLog);

        $this->gearMaintenanceLogRepository->delete($command->getGearMaintenanceLogId());
    }
}
