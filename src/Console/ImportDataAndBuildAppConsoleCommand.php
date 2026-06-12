<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Daemon\RunFileImportAndBuildAppConsoleCommand;
use App\Console\Daemon\RunStravaImportAndBuildAppConsoleCommand;
use App\Domain\Import\ImportMode;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** @deprecated Use app:cron:run-file-import and app:cron:run-strava-import */
#[AsCommand(name: 'app:data:import|app:data:build|app:strava:import-data|app:strava:build-files', description: 'Import and build activity data')]
final class ImportDataAndBuildAppConsoleCommand extends Command
{
    use ProvideConsoleIntro;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ImportMode $importMode,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));
        $this->outputConsoleIntro($output);

        $application = $this->getApplication();
        $usedConsoleCommand = $input->getFirstArgument();
        assert($application instanceof Application);

        $commandToDelegateTo = match ($this->importMode) {
            ImportMode::STRAVA_API => RunStravaImportAndBuildAppConsoleCommand::NAME,
            ImportMode::FILES => RunFileImportAndBuildAppConsoleCommand::NAME,
        };

        $optionToUse = match ($usedConsoleCommand) {
            'app:strava:import-data', 'app:data:import' => RunStravaImportAndBuildAppConsoleCommand::SKIP_BUILD_OPTION,
            'app:strava:build-files', 'app:data:build' => RunStravaImportAndBuildAppConsoleCommand::SKIP_IMPORT_OPTION,
            default => throw new \RuntimeException(sprintf('Unknown command "%s"', $usedConsoleCommand)),
        };

        $arrayInput = [
            'command' => $commandToDelegateTo,
            '--'.$optionToUse => true,
        ];

        $input = new ArrayInput($arrayInput);
        $input->setInteractive(false);

        return $application->doRun($input, $output);
    }
}
