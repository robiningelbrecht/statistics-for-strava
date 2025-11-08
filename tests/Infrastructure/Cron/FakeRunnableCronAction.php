<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Cron;

use App\Infrastructure\Cron\RunnableCronAction;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function run(OutputInterface $output): void
    {
        $output->writeln('RunnableCronAction fake has been called');
    }
}
