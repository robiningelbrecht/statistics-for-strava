<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621191636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Gear DROP COLUMN distanceInMeter');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Gear ADD COLUMN distanceInMeter INTEGER NOT NULL DEFAULT 0');
    }
}
