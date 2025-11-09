<?php

namespace App\Console;

use App\BuildApp\AppVersion;
use App\BuildApp\ImportAndBuildAppCronAction;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
final class BuildAppConsoleCommand extends Command
{
    public function __construct(
        private readonly ImportAndBuildAppCronAction $importAndBuildAppCronAction,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));
        /** @var \Symfony\Component\Console\Application $consoleApplication */
        $consoleApplication = $this->getApplication();

        $output->block(
            messages: sprintf('Statistics for Strava %s', AppVersion::getSemanticVersion()),
            style: 'fg=black;bg=green',
            padding: true
        );

        $this->importAndBuildAppCronAction->setConsoleApplication($consoleApplication);
        $this->importAndBuildAppCronAction->runBuild($output);

        return Command::SUCCESS;
    }
}
