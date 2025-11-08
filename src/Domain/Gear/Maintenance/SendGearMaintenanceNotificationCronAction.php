<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use Symfony\Component\Console\Output\OutputInterface;

class SendGearMaintenanceNotificationCronAction implements RunnableCronAction
{
    public function __construct()
    {
    }

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
        sleep(5);
        $output->writeln('RunnableCronAction test2');
    }
}
