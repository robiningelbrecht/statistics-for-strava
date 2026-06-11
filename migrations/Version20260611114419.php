<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611114419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity DROP COLUMN kudoCount');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Activity ADD COLUMN kudoCount INTEGER NOT NULL DEFAULT 0');
    }
}
