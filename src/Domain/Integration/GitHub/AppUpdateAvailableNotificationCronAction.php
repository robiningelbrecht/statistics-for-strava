<?php

declare(strict_types=1);

namespace App\Domain\Integration\GitHub;

use App\Application\AppVersion;
use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Cron\RunnableCronAction;
use App\Infrastructure\ValueObject\String\Url;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class AppUpdateAvailableNotificationCronAction implements RunnableCronAction
{
    public function __construct(
        private GitHub $gitHub,
        private CommandBus $commandBus,
    ) {
    }

    public function getId(): string
    {
        return 'appUpdateAvailableNotification';
    }

    public function requiresDatabaseSchemaToBeUpdated(): bool
    {
        return true;
    }

    public function getMutexTtl(): int
    {
        return 60;
    }

    public function run(SymfonyStyle $output): void
    {
        $latestRelease = $this->gitHub->getLatestRelease();
        if (AppVersion::getSemanticVersion() === $latestRelease) {
            return;
        }

        $this->commandBus->dispatch(new SendNotification(
            title: 'New app version available',
            message: sprintf("We have been busy, %s is finally out! Go see what's new.", $latestRelease),
            tags: ['partying_face'],
            actionUrl: Url::fromString('https://github.com/robiningelbrecht/statistics-for-strava/releases'),
        ));
    }
}
