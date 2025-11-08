<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon;

use App\Infrastructure\Daemon\Cron\Cron;
use App\Infrastructure\Time\Clock\Clock;
use React\ChildProcess\Process;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

use WyriHaximus\React\Cron\Action;
use function React\Promise\resolve;

final readonly class Daemon
{
    public function __construct(
        private Clock $clock,
        private Cron $cron,
    ) {
    }

    public function configureCron(OutputInterface $output): void
    {
        $actions = [];
        /** @var \App\Infrastructure\Daemon\Cron\CronAction $cronAction */
        foreach ($this->cron as $cronAction) {
            $actions[] = new Action(
                key: $cronAction->getId(),
                mutexTtl: $cronAction->getRunnable()->getMutexTtl(),
                expression: (string) $cronAction->getExpression(),
                performer: function () use ($output, $cronAction): PromiseInterface {
                    $output->writeln(sprintf(
                        '<info>[%s] Starting cron action "%s"</info>',
                        $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                        $cronAction->getId()
                    ));
                    $process = new Process('bin/console app:cron:action '.$cronAction->getId());
                    $process->start();

                    $process->stdout?->on('data', function (string $chunk) use ($output): void {
                        $output->write($chunk);
                    });

                    $process->stderr?->on('data', function (string $chunk) use ($output): void {
                        $output->write(sprintf('<error>%s</error>', $chunk));
                    });

                    $process->on('exit', function () use ($output, $cronAction): void {
                        $output->writeln(sprintf(
                            '<info>[%s] Finished cron action "%s"</info>',
                            $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                            $cronAction->getId()
                        ));
                    });

                    return resolve(true);
                }
            );
        }

        $cron = \WyriHaximus\React\Cron::create(...$actions)->on('error', static function (\Throwable $throwable) use ($output): void {
            $output->writeln(sprintf('<error>%s</error>', $throwable->getCode()));
        });

        if (empty($actions)) {
            $output->writeln(sprintf('<info>%s</info>', 'No cron items configured, shutting down cron...'));
            $cron->stop();
        }
    }
}
