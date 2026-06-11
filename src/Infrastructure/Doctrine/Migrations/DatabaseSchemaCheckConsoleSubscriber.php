<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DatabaseSchemaCheckConsoleSubscriber implements EventSubscriberInterface
{
    private bool $blocked = false;

    public function __construct(
        private readonly MigrationRunner $migrationRunner,
    ) {
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof \Symfony\Component\Console\Command\Command) {
            return;
        }

        $reflection = new \ReflectionClass($command);
        if ([] === $reflection->getAttributes(RequiresUpToDateDatabaseSchema::class)) {
            return;
        }

        try {
            $databaseIsAtLatestVersion = $this->migrationRunner->isAtLatestVersion();
        } catch (ConnectionException) {
            $databaseIsAtLatestVersion = false;
        }

        if ($databaseIsAtLatestVersion) {
            return;
        }

        $event->getOutput()->writeln('<error>Your database is not up to date with the migration schema. Restart your docker containers by running docker compose stop && docker compose up -d</error>');
        $event->disableCommand();
        $this->blocked = true;
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if ($this->blocked) {
            $event->setExitCode(0);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }
}
