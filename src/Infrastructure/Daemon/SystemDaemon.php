<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon;

use App\Domain\Strava\Webhook\WebhookConfig;
use App\Infrastructure\Console\ConsoleOutputAware;
use App\Infrastructure\Daemon\Cron\Cron;
use App\Infrastructure\Daemon\Cron\CronAction;
use App\Infrastructure\Daemon\Cron\CronProcess;
use App\Infrastructure\Time\Clock\Clock;
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
        private readonly WebhookConfig $webhookConfig,
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
                    $process = new CronProcess(
                        cronActionId: $cronAction->getId(),
                        clock: $this->clock,
                        output: $this->getConsoleOutput(),
                    );
                    $process->start();

                    return resolve(true);
                }
            );
        }

        $extraConfiguredCronActionsOutput = [];
        if ($this->webhookConfig->isEnabled()) {
            $extraConfiguredCronActionsOutput[] = sprintf('<info> - processStravaWebhooks: %s</info>', $this->webhookConfig->getCronExpression());
            $actions[] = new Action(
                key: 'processStravaWebhooks',
                mutexTtl: 60,
                expression: (string) $this->webhookConfig->getCronExpression(),
                performer: function (): PromiseInterface {
                    $process = new CronProcess(
                        cronActionId: 'processStravaWebhooks',
                        clock: $this->clock,
                        output: $this->getConsoleOutput(),
                    )->withCommand('bin/console app:cron:process-webhooks');
                    $process->start();

                    return resolve(true);
                }
            );
        }

        \WyriHaximus\React\Cron::create(...$actions)->on('error', function (\Throwable $throwable): void {
            $this->getConsoleOutput()->writeln(sprintf('<error>%s</error>', $throwable->getMessage()));
        });

        if (empty($actions)) {
            $this->getConsoleOutput()->writeln(sprintf('<info>%s</info>', 'No cron items configured, shutting down cron...'));

            return;
        }

        $this->getConsoleOutput()->writeln(sprintf('<info>%s</info>', 'Cron configured'));
        $this->getConsoleOutput()->writeln([
            ...array_map(
                fn (CronAction $action): string => \sprintf('<info> - %s: %s</info>', $action->getId(), $action->getExpression()),
                iterator_to_array($this->cron)
            ),
            ...$extraConfiguredCronActionsOutput,
        ]);
    }
}
