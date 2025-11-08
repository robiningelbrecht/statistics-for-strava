<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.cron_action')]
interface RunnableCronAction
{
    public function getId(): string;

    public function getMutexTtl(): int;

    public function run(OutputInterface $output): void;
}
