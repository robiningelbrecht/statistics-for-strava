<?php

namespace App\Console;

use App\Application\RunImport\RunImport;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    use ProvideConsoleIntro;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ResourceUsage $resourceUsage,
        private readonly LoggerInterface $logger,
        private readonly Mutex $mutex,
        private readonly MigrationRunner $migrationRunner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('restrictToActivityId', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));
        $this->resourceUsage->startTimer();
        $this->outputConsoleIntro($output);

        $restrictToActivityIds = null;
        if ($restrictToActivityId = $input->getArgument('restrictToActivityId')) {
            $restrictToActivityIds = ActivityIds::fromArray([ActivityId::fromUnprefixed($restrictToActivityId)]);
        }

        $this->migrationRunner->run($output);
        $this->mutex->acquireLock('ImportStravaDataConsoleCommand');

        $this->commandBus->dispatch(new RunImport(
            output: $output,
            restrictToActivityIds: $restrictToActivityIds,
        ));

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
