<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\BuildApp\AppUrl;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class GearMaintenanceNotificationCronAction implements RunnableCronAction
{
    public function __construct(
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
        private AppUrl $appUrl,
        private CommandBus $commandBus,
    ) {
    }

    public function getId(): string
    {
        return 'gearMaintenanceNotification';
    }

    public function getMutexTtl(): int
    {
        return 60;
    }

    public function run(SymfonyStyle $output): void
    {
        if ($this->maintenanceTaskProgressCalculator->getGearIdsThatHaveDueTasks()->isEmpty()) {
            return;
        }
        $this->commandBus->dispatch(new SendNotification(
            title: 'Gear maintenance is due',
            message: 'One of your gear components needs some love. Fix it up before it falls apart!',
            tags: ['hammer_and_wrench'],
            actionUrl: $this->appUrl,
        ));
    }
}
