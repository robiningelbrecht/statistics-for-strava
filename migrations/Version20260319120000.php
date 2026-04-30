<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ActivityImportHash (activityId VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, PRIMARY KEY(activityId))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ActivityImportHash');
    }
}
