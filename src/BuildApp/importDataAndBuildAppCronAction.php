<?php

declare(strict_types=1);

namespace App\BuildApp;

use App\BuildApp\BuildApp\BuildApp;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Domain\Strava\ImportStravaData\ImportStravaData;
use App\Infrastructure\Console\ConsoleApplicationAware;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Style\SymfonyStyle;

final class importDataAndBuildAppCronAction implements RunnableCronAction
{
    use ConsoleApplicationAware;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ResourceUsage $resourceUsage,
        private readonly AppUrl $appUrl,
    ) {
    }

    public function getId(): string
    {
        return 'importDataAndBuildApp';
    }

    public function getMutexTtl(): int
    {
        return 1800;
    }

    public function run(SymfonyStyle $output): void
    {
        $output->block(
            messages: sprintf('Statistics for Strava %s', AppVersion::getSemanticVersion()),
            style: 'fg=black;bg=green',
            padding: true
        );

        $this->resourceUsage->startTimer();

        $this->commandBus->dispatch(new ImportStravaData(
            consoleApplication: $this->getConsoleApplication(),
            output: $output,
        ));
        $this->commandBus->dispatch(new BuildApp(
            consoleApplication: $this->getConsoleApplication(),
            output: $output,
        ));

        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: sprintf('New import and build of your Strava stats was successful in %ss', $this->resourceUsage->getRunTimeInSeconds()),
            tags: ['+1'],
            actionUrl: $this->appUrl
        ));

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));
    }
}
