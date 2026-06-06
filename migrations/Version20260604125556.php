<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604125556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE FileImport (fileImportId VARCHAR(255) NOT NULL, originalFilename VARCHAR(255) NOT NULL, fileHash VARCHAR(255) NOT NULL, fileContents BLOB DEFAULT NULL, source VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, errorMessage CLOB DEFAULT NULL, activityId VARCHAR(255) DEFAULT NULL, importedOn DATETIME NOT NULL, PRIMARY KEY (fileImportId))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FileImport_fileHash ON FileImport (fileHash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE FileImport');
    }
}
