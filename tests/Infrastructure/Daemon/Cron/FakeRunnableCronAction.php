<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Daemon\Cron;

use App\Infrastructure\Console\ConsoleApplicationAware;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use Symfony\Component\Console\Style\SymfonyStyle;

final class FakeRunnableCronAction implements RunnableCronAction
{
    use ConsoleApplicationAware;

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
