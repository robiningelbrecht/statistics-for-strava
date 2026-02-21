<?php

declare(strict_types=1);

namespace App\Application\RunBuild;

use App\Application\Build\ConfigureAppColors\ConfigureAppColors;
use App\Application\Import\ImportGear\GearImportStatus;
use App\Domain\Activity\ActivityIdRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Daemon\ProcessFactory;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use Symfony\Component\Process\Process;

final readonly class RunBuildCommandHandler implements CommandHandler
{
    private const int DEFAULT_MAX_CONCURRENCY = 2;
    private const int PROCESS_TIMEOUT_IN_SECONDS = 600;

    public function __construct(
        private CommandBus $commandBus,
        private ActivityIdRepository $activityIdRepository,
        private GearImportStatus $gearImportStatus,
        private MigrationRunner $migrationRunner,
        private ProcessFactory $processFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof RunBuild);

        $output = $command->getOutput();
        if (!$this->migrationRunner->isAtLatestVersion()) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command before building the HTML files</error>');

            return;
        }
        if ($this->activityIdRepository->count() <= 0) {
            $output->writeln('<error>Wait until at least one Strava activity has been imported before building the app</error>');

            return;
        }

        if (!$this->gearImportStatus->isComplete()) {
            $output->block('[WARNING] Some of your gear has not been imported yet. This is most likely due to Strava API rate limits being reached. As a result, your gear statistics may currently be incomplete.

This is not a bug, once all your activities have been imported, your gear statistics will update automatically and be complete.', null, 'fg=black;bg=yellow', ' ', true);
        }

        $output->writeln('Building app...');
        $output->newLine();

        $this->commandBus->dispatch(new ConfigureAppColors());

        $steps = BuildStep::cases();
        $maxLabelLength = max(array_map(fn (BuildStep $step): int => mb_strlen($step->getLabel()), $steps));
        $maxConcurrency = min(self::DEFAULT_MAX_CONCURRENCY, count($steps));
        $queue = $steps;
        /** @var array<string, array{process: Process, step: BuildStep}> $running */
        $running = [];
        /** @var array<string, string> $failures */
        $failures = [];

        while ([] !== $queue || [] !== $running) {
            while (count($running) < $maxConcurrency && [] !== $queue) {
                $step = array_shift($queue);
                $process = $this->processFactory->create(
                    ['bin/console', 'app:strava:build-step', $step->value]
                );
                $process->setTimeout(self::PROCESS_TIMEOUT_IN_SECONDS);
                $process->start();
                $running[$step->value] = ['process' => $process, 'step' => $step];
            }

            foreach ($running as $key => ['process' => $process, 'step' => $step]) {
                if ($process->isRunning()) {
                    continue;
                }

                if (!$process->isSuccessful()) {
                    $output->writeln(sprintf('  <fg=red>×</> %s', $step->getLabel()));
                    $failures[$step->value] = $process->getErrorOutput() ?: $process->getOutput();
                    unset($running[$key]);

                    continue;
                }

                $processOutput = trim($process->getOutput());
                $paddedLabel = str_pad($step->getLabel(), $maxLabelLength);
                $stepLabel = '' !== $processOutput && '0' !== $processOutput
                    ? sprintf('  <info>✓</info> %s <fg=gray>(%s)</>', $paddedLabel, $processOutput)
                    : sprintf('  <info>✓</info> %s', $step->getLabel());
                $output->writeln($stepLabel);
                unset($running[$key]);
            }

            if ([] !== $running) {
                usleep(50_000);
            }
        }

        $output->writeln('');

        if ([] !== $failures) {
            $failedSteps = implode(', ', array_keys($failures));
            $failureDetails = implode("\n\n", array_map(
                fn (string $step, string $message): string => sprintf('[%s] %s', $step, $message),
                array_keys($failures),
                array_values($failures),
            ));
            throw new \RuntimeException(sprintf("Build step(s) \"%s\" failed:\n\n%s", $failedSteps, $failureDetails));
        }
    }
}
