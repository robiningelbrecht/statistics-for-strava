<?php

declare(strict_types=1);

namespace App\Console\Build;

use App\Application\Build\ConfigureAppLocale\ConfigureAppLocale;
use App\Application\RunBuild\BuildStep;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[WithMonologChannel('console-output')]
#[AsCommand(name: 'app:strava:build-step', description: 'Run a single build step')]
final class BuildStepConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Clock $clock,
        private readonly ResourceUsage $resourceUsage,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('step', InputArgument::REQUIRED | InputArgument::IS_ARRAY, sprintf(
            'The build step(s) to run (%s)',
            implode(', ', array_map(fn (BuildStep $step) => $step->value, BuildStep::cases()))
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $steps = array_map(
            fn (string $step): BuildStep => BuildStep::from($step),
            $input->getArgument('step'),
        );

        $maxLabelLength = max(array_map(
            fn (BuildStep $step): int => mb_strlen($step->getLabel()),
            BuildStep::cases(),
        ));

        $now = $this->clock->getCurrentDateTimeImmutable();
        $this->commandBus->dispatch(new ConfigureAppLocale());
        foreach ($steps as $step) {
            try {
                $this->resourceUsage->startTimer();
                $this->commandBus->dispatch($step->createCommand($now));
                $this->resourceUsage->stopTimer();

                $paddedLabel = str_pad($step->getLabel(), $maxLabelLength);
                $output->writeln(sprintf('  <info>✓</info> %s <fg=gray>(%s)</>', $paddedLabel, $this->resourceUsage->format()));
            } catch (\Throwable $e) {
                $output->writeln(sprintf('  <fg=red>×</> %s. Check the logs for more info', $step->getLabel()));
                $this->logger->error(sprintf(
                    '%s on line %s: %s',
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ));
            }
        }

        return Command::SUCCESS;
    }
}
