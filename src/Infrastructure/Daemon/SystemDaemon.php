<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon;

use App\Infrastructure\Console\ConsoleOutputAware;
use App\Infrastructure\Daemon\Cron\Cron;
use App\Infrastructure\Daemon\Cron\CronAction;
use App\Infrastructure\Time\Clock\Clock;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use WyriHaximus\React\Cron\Action;

use function React\Promise\resolve;

/**
 * @codeCoverageIgnore
 */
final class SystemDaemon implements Daemon
{
    use ConsoleOutputAware;

    public function __construct(
        private readonly Clock $clock,
        private readonly Cron $cron,
    ) {
    }

    public function addPeriodicDebugTimer(): void
    {
        Loop::addPeriodicTimer(1.0, function (): void {
            $this->getConsoleOutput()->writeln(sprintf(
                '[%s] Periodic debug timer',
                $this->clock->getCurrentDateTimeImmutable()->format('H:i:s'),
            ));
        });
    }

    public function configureCron(): void
    {
        $actions = [];
        /** @var CronAction $cronAction */
        foreach ($this->cron as $cronAction) {
            $actions[] = new Action(
                key: $cronAction->getId(),
                mutexTtl: $cronAction->getRunnable()->getMutexTtl(),
                expression: (string) $cronAction->getExpression(),
                performer: function () use ($cronAction): PromiseInterface {
                    $this->getConsoleOutput()->writeln(sprintf(
                        '<info>[%s] Starting cron action "%s"</info>',
                        $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                        $cronAction->getId()
                    ));
                    $process = new Process('bin/console app:cron:action '.$cronAction->getId());
                    $process->start();

                    $process->stdout?->on('data', function (string $chunk): void {
                        $this->getConsoleOutput()->write($chunk);
                    });

                    $process->stderr?->on('data', function (string $chunk): void {
                        $this->getConsoleOutput()->write(sprintf('<error>%s</error>', $chunk));
                    });

                    $process->on('exit', function () use ($cronAction): void {
                        $this->getConsoleOutput()->writeln(sprintf(
                            '<info>[%s] Finished cron action "%s"</info>',
                            $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                            $cronAction->getId()
                        ));
                    });

                    return resolve(true);
                }
            );
        }

        if (empty($actions)) {
            $this->getConsoleOutput()->writeln(sprintf('<info>%s</info>', 'No cron items configured, shutting down cron...'));

            return;
        }

        \WyriHaximus\React\Cron::create(...$actions)->on('error', function (\Throwable $throwable): void {
            $this->getConsoleOutput()->writeln(sprintf('<error>%s</error>', $throwable->getMessage()));
        });

        $this->getConsoleOutput()->writeln(sprintf('<info>%s</info>', 'Cron configured'));
        $this->getConsoleOutput()->writeln(array_map(
            fn (CronAction $action): string => \sprintf('<info> - %s: %s</info>', $action->getId(), $action->getExpression()),
            iterator_to_array($this->cron)
        ));
    }
}
