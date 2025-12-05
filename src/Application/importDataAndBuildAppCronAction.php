<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Build\BuildApp\BuildApp;
use App\Application\Import\ImportStravaData\ImportStravaData;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class importDataAndBuildAppCronAction implements RunnableCronAction
{
    use ProvideConsoleIntro;

    public function __construct(
        private CommandBus $commandBus,
        private ResourceUsage $resourceUsage,
        private AppUrl $appUrl,
    ) {
    }

    public function getId(): string
    {
        return 'importDataAndBuildApp';
    }

    public function requiresDatabaseSchemaToBeUpdated(): bool
    {
        return false;
    }

    public function getMutexTtl(): int
    {
        return 1800;
    }

    public function run(SymfonyStyle $output): void
    {
        $this->outputConsoleIntro($output);

        $this->resourceUsage->startTimer();

        $this->commandBus->dispatch(new ImportStravaData(
            output: $output,
        ));
        $this->commandBus->dispatch(new BuildApp(
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
