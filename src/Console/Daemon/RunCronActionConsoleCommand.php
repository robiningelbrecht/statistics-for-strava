<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\Infrastructure\Daemon\Cron\Cron;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:cron:action', description: 'Run a cron action')]
final class RunCronActionConsoleCommand extends Command
{
    public function __construct(
        private readonly Cron $cron,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('cronActionId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runnableCronActionId = $input->getArgument('cronActionId');

        $this->cron->getRunnable($runnableCronActionId)->run($output);

        return Command::SUCCESS;
    }
}
