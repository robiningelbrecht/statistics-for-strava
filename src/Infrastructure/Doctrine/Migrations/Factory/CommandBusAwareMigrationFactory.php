<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations\Factory;

use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;

readonly class CommandBusAwareMigrationFactory implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        private CommandBus $commandBus,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->migrationFactory->createVersion($migrationClassName);
        if ($migration instanceof CommandBusAwareMigration) {
            $migration->setCommandBus($this->commandBus);
        }

        return $migration;
    }
}
