<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210175326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE Activity SET location = JSON_SET(location, "$.is_reverse_geocoded", true)');
        $this->addSql('ALTER TABLE Activity RENAME COLUMN location TO routeGeography');
    }

    public function down(Schema $schema): void
    {
    }
}
