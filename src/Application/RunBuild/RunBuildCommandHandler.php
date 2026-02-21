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
    private const int PROCESS_TIMEOUT_PER_STEP_IN_SECONDS = 600;

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

        $this->commandBus->dispatch(new ConfigureAppColors());

        $groups = BuildStep::getProcessGroups();
        $allSteps = array_merge(...$groups);
        $output->writeln(sprintf('Running %d build steps on %d parallel processes.', count($allSteps), count($groups)));
        $output->newLine();

        /** @var array<int, array{process: Process, steps: BuildStep[]}> $running */
        $running = [];

        foreach ($groups as $steps) {
            $stepValues = array_map(fn (BuildStep $step): string => $step->value, $steps);
            $process = $this->processFactory->create(
                ['bin/console', '--ansi', 'app:strava:build-step', ...$stepValues]
            );
            $process->setTimeout(self::PROCESS_TIMEOUT_PER_STEP_IN_SECONDS * count($steps));
            $process->start(function (string $type, string $buffer) use ($output): void {
                if (Process::OUT === $type) {
                    $output->write($buffer);
                }
            });
            $running[] = ['process' => $process, 'steps' => $steps];
        }

        while ([] !== $running) {
            foreach ($running as $key => ['process' => $process, 'steps' => $groupSteps]) {
                if ($process->isRunning()) {
                    continue; // @codeCoverageIgnore
                }

                unset($running[$key]);
            }

            if ([] !== $running) {
                usleep(50_000); // @codeCoverageIgnore
            }
        }

        $output->writeln('');
    }
}
