<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use App\Infrastructure\Console\ConsoleApplication;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class DoctrineMigrationRunner implements MigrationRunner
{
    private const string SQUASHED_MIGRATION = 'DoctrineMigrations\\Version20260130000000';
    private const string LAST_MIGRATION_BEFORE_SQUASH = 'DoctrineMigrations\\Version20260128120916';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function run(OutputInterface $output): void
    {
        $this->handleMigrationSquash();

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
        ]);
        $input->setInteractive(false);
        $exitCode = ConsoleApplication::get()->doRun(
            input: $input,
            output: $output
        );

        if (0 !== $exitCode) {
            throw new CouldNotRunMigrations();
        }
    }

    public function isAtLatestVersion(): bool
    {
        $output = new MigrationConsoleOutput();
        ConsoleApplication::get()->doRun(
            new ArrayInput([
                'command' => 'doctrine:migrations:status',
            ]),
            $output
        );

        return str_contains($output->getDisplay(), 'Already at latest version');
    }

    private function handleMigrationSquash(): void
    {
        if (!$this->migrationVersionsTableExists()) {
            // Fresh install, let migrations run normally.
            // The squashed migration will create the schema.
            return;
        }

        if ($this->hasExecutedMigration(self::SQUASHED_MIGRATION)) {
            // Squashed migration already executed, nothing to do.
            return;
        }

        if (!$this->hasExecutedMigration(self::LAST_MIGRATION_BEFORE_SQUASH)) {
            // User was not at the latest version before squash.
            // They need to update to the last version before the squash first.
            throw new MigrationsOutdated();
        }

        // User was at the latest version before squash.
        // Mark the squashed migration as executed without running it,
        // so the user doesn't notice the squash.
        $this->markMigrationAsExecuted();
    }

    private function migrationVersionsTableExists(): bool
    {
        return $this->connection->createSchemaManager()->tablesExist(['migration_versions']);
    }

    private function hasExecutedMigration(string $version): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM migration_versions WHERE version = :version',
            ['version' => $version]
        );

        return (int) $result > 0;
    }

    private function markMigrationAsExecuted(): void
    {
        $this->connection->executeStatement('DELETE FROM migration_versions');
        $this->connection->insert('migration_versions', [
            'version' => self::SQUASHED_MIGRATION,
            'executed_at' => SerializableDateTime::fromString('now')->format('Y-m-d H:i:s'),
            'execution_time' => 0,
        ]);
    }
}
