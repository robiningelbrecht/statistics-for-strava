<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

use Symfony\Component\Console\Output\OutputInterface;

final readonly class AppUpdateAvailableNotificationCronAction implements RunnableCronAction
{
    public function getId(): string
    {
        return 'appUpdateAvailableNotification';
    }

    public function getMutexTtl(): int
    {
        return 60;
    }

    public function run(OutputInterface $output): void
    {
        // TODO: Implement run() method.
    }
}
