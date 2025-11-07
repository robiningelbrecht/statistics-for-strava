<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Infrastructure\Cron\RunnableCronAction;
use Symfony\Component\Console\Output\OutputInterface;

class GearMaintenanceNotificationCronAction implements RunnableCronAction
{
    public function getId(): string
    {
        return 'gearMaintenanceNotification';
    }

    public function run(OutputInterface $output): void
    {
        $output->writeln('RunnableCronAction test');
    }
}
