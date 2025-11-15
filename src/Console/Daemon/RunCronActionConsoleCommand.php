<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\Infrastructure\Daemon\Cron\Cron;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:cron:action', description: 'Run a cron action')]
final class RunCronActionConsoleCommand extends Command
{
    public function __construct(
        private readonly Cron $cron,
        private readonly MigrationRunner $migrationRunner,
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

        try {
            $databaseIsAtLatestVersion = $this->migrationRunner->isAtLatestVersion();
        } catch (ConnectionException) {
            $databaseIsAtLatestVersion = false;
        }

        if (!$databaseIsAtLatestVersion) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command.</error>');

            return Command::SUCCESS;
        }

        $runnable = $this->cron->getRunnable($runnableCronActionId);
        $runnable->run(new SymfonyStyle($input, $output));

        return Command::SUCCESS;
    }
}
