<?php

namespace App\Console;

use App\BuildApp\AppUrl;
use App\BuildApp\BuildApp\BuildApp;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
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
#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
final class BuildAppConsoleCommand extends Command
{
    use ProvideConsoleIntro;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ResourceUsage $resourceUsage,
        private readonly AppUrl $appUrl,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));
        $this->resourceUsage->startTimer();

        $this->outputConsoleIntro($output);

        $this->commandBus->dispatch(new BuildApp(
            output: $output,
        ));

        $this->resourceUsage->stopTimer();
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New build of your Strava stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1'],
            actionUrl: $this->appUrl
        ));

        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
