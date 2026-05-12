<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512173300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity ADD COLUMN kilojoules INTEGER DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement(
            'UPDATE Activity SET kilojoules = CAST(JSON_EXTRACT(data, \'$.kilojoules\') AS INTEGER) WHERE JSON_EXTRACT(data, \'$.kilojoules\') IS NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity DROP COLUMN kilojoules');
    }
}
