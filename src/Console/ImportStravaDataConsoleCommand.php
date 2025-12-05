<?php

namespace App\Console;

use App\Application\RunImport\RunImport;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    use ProvideConsoleIntro;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ResourceUsage $resourceUsage,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));

        $this->outputConsoleIntro($output);
        $this->resourceUsage->startTimer();

        $this->commandBus->dispatch(new RunImport(
            output: $output,
        ));

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
