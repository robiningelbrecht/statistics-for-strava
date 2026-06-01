<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601053922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity ADD COLUMN elapsedTimeInSeconds INTEGER DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement(
            'UPDATE Activity SET elapsedTimeInSeconds = CAST(JSON_EXTRACT(data, \'$.elapsed_time\') AS INTEGER) WHERE JSON_EXTRACT(data, \'$.elapsed_time\') IS NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity DROP COLUMN elapsedTimeInSeconds');
    }
}
