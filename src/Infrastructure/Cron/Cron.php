<?php

declare(strict_types=1);

namespace App\Infrastructure\Cron;

use App\Infrastructure\Console\ConsoleOutputAware;
use App\Infrastructure\Time\Clock\Clock;
use React\ChildProcess\Process;
use React\Promise\PromiseInterface;
use WyriHaximus\React\Cron\Action;

use function React\Promise\resolve;

final class Cron
{
    use ConsoleOutputAware;

    public function __construct(
        private readonly ConfiguredCronActions $configuredCronActions,
        private readonly Clock $clock,
    ) {
    }

    public function create(): void
    {
        $actions = [];
        $output = $this->getConsoleOutput();

        foreach ($this->configuredCronActions as $configuredCronAction) {
            $actions[] = new Action(
                key: $configuredCronAction->getId(),
                mutexTtl: 60,
                expression: (string) $configuredCronAction->getCronExpression(),
                performer: function () use ($output, $configuredCronAction): PromiseInterface {
                    $output?->writeln(sprintf(
                        '<info>[%s] Starting cron action "%s"</info>',
                        $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                        $configuredCronAction->getId()
                    ));
                    $process = new Process('bin/console app:cron:action '.$configuredCronAction->getId());
                    $process->start();

                    $process->stdout?->on('data', function (string $chunk) use ($output) {
                        $output?->write($chunk);
                    });

                    $process->stderr?->on('data', function (string $chunk) use ($output) {
                        $output?->write(sprintf('<error>%s</error>', $chunk));
                    });

                    $process->on('exit', function () use ($output, $configuredCronAction) {
                        $output?->writeln(sprintf(
                            '<info>[%s] Finished cron action "%s"</info>',
                            $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s'),
                            $configuredCronAction->getId()
                        ));
                    });

                    return resolve(true);
                }
            );
        }

        $cron = \WyriHaximus\React\Cron::create(...$actions)->on('error', static function (\Throwable $throwable) use ($output): void {
            $output?->writeln(sprintf('<error>%s</error>', $throwable->getCode()));
        });

        if (empty($actions)) {
            $output?->writeln(sprintf('<info>%s</info>', 'No cron items configured, shutting down cron...'));
            $cron->stop();
        }
    }
}
