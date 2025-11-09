<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.cron_action')]
interface RunnableCronAction
{
    public function getId(): string;

    /**
     * TTL for the mutex lock, always set this way higher than the expected execution time,
     * but low enough any failures during the run will cause issues.
     */
    public function getMutexTtl(): int;

    public function run(OutputInterface $output): void;
}
