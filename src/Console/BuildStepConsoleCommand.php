<?php

declare(strict_types=1);

namespace App\Console;

use App\Application\Build\ConfigureAppLocale\ConfigureAppLocale;
use App\Application\RunBuild\BuildStep;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:build-step', description: 'Run a single build step')]
final class BuildStepConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Clock $clock,
        private readonly ResourceUsage $resourceUsage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('step', InputArgument::REQUIRED, sprintf(
            'The build step to run (%s)',
            implode(', ', array_map(fn (BuildStep $step) => $step->value, BuildStep::cases()))
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $step = BuildStep::from($input->getArgument('step'));

        $this->resourceUsage->startTimer();

        $this->commandBus->dispatch(new ConfigureAppLocale());
        $this->commandBus->dispatch($step->createCommand(
            $this->clock->getCurrentDateTimeImmutable(),
        ));

        $this->resourceUsage->stopTimer();
        $output->writeln($this->resourceUsage->format());

        return Command::SUCCESS;
    }
}
