<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\BuildApp\AppVersion;
use App\Infrastructure\Cron\Cron;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Time\Clock\Clock;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

        $this->cron->setConsoleOutput($output);
        $this->cron->create();

        Loop::addPeriodicTimer(1.0, function () {
            echo '['.date('H:i:s').'] PeriodicTimer'.PHP_EOL;
        });

        return Command::SUCCESS;
    }
}
