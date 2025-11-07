<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\BuildApp\AppVersion;
use App\Infrastructure\Cron\Cron;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Time\Clock\Clock;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WyriHaximus\React\Cron\Action;

use function React\Promise\resolve;

#[WithMonologChannel('daemon')]
#[AsCommand(name: 'app:daemon:run', description: 'Start SFS daemon')]
final class RunDaemonConsoleCommand extends Command
{
    public function __construct(
        private readonly Clock $clock,
        private readonly Cron $cron,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));

        $output->block(
            messages: [
                sprintf('Statistics for Strava %s | DAEMON', AppVersion::getSemanticVersion()),
                sprintf('Started on %s', $this->clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s')),
            ],
            style: 'fg=black;bg=green',
            padding: true
        );

        $actions = [];
        /** @var \App\Infrastructure\Cron\CronAction $cronAction */
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

        Loop::addPeriodicTimer(1.0, function (): void {
            echo '['.date('H:i:s').'] PeriodicTimer'.PHP_EOL;
        });

        return Command::SUCCESS;
    }
}
