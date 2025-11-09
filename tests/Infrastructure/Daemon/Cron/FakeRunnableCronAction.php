<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Daemon\Cron;

use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class FakeRunnableCronAction implements RunnableCronAction
{
    public function getId(): string
    {
        return 'fake';
    }

    public function getMutexTtl(): int
    {
        return 60;
    }

    public function run(SymfonyStyle $output): void
    {
        $output->writeln('RunnableCronAction fake has been called');
    }
}
