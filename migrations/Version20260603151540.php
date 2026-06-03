<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260603151540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Activity ADD COLUMN importSource VARCHAR(255) NOT NULL DEFAULT 'stravaApi'");
        $this->addSql('ALTER TABLE Activity ADD COLUMN externalReferenceId VARCHAR(255) DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement("UPDATE Activity SET importSource = 'stravaApi'");
        $this->connection->executeStatement(
            "UPDATE Activity SET externalReferenceId = JSON_EXTRACT(data, '$.external_id') WHERE JSON_EXTRACT(data, '$.external_id') IS NOT NULL"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity DROP COLUMN importSource');
        $this->addSql('ALTER TABLE Activity DROP COLUMN externalReferenceId');
    }
}
