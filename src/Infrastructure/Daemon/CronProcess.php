<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon;

use App\Infrastructure\Time\Clock\Clock;
use React\ChildProcess\Process;
use Symfony\Component\Console\Output\OutputInterface;

final class CronProcess
{
    private ?Process $process = null;

    public function __construct(
        private readonly string $cronActionId,
        private readonly Clock $clock,
        private readonly OutputInterface $output,
    ) {
    }

    public function withCommand(string $command): self
    {
        $cronProcess = clone $this;

        $cronProcess->process = new Process($command);

        return $cronProcess;
    }

    public function start(): void
    {
        $this->output->writeln(sprintf(
            '<info>[%s] Starting cron action "%s"</info>',
            $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
            $this->cronActionId
        ));

        $process = $this->process ??
            new Process('bin/console app:cron:action '.$this->cronActionId);
        $process->start();

        $process->stdout?->on('data', function (string $chunk): void {
            $this->output->write($chunk);
        });

        $process->stderr?->on('data', function (string $chunk): void {
            $this->output->write(sprintf('<error>%s</error>', $chunk));
        });

        $process->on('exit', function (): void {
            $this->output->writeln(sprintf(
                '<info>[%s] Finished cron action "%s"</info>',
                $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                $this->cronActionId
            ));
        });
    }
}
