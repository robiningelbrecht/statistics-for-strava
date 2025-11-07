<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Infrastructure\Cron\RunnableCronAction;
use Symfony\Component\Console\Output\OutputInterface;

class SendGearMaintenanceNotificationCronAction implements RunnableCronAction
{
    public function getId(): string
    {
        return 'sendGearMaintenanceNotification';
    }

    public function getMutexTtl(): int
    {
        return 60;
    }

    public function run(OutputInterface $output): void
    {
        $output->writeln('RunnableCronAction test');
    }
}
