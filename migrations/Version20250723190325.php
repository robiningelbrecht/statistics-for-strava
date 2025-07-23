<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250723190325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ChatMessage (messageId VARCHAR(255) NOT NULL, message CLOB NOT NULL, messageRole VARCHAR(255) NOT NULL, "on" DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(messageId))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ChatMessage');
    }
}
