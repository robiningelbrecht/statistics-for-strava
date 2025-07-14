<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250714175754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add polyline column to Segment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Segment ADD COLUMN polyline TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Segment DROP COLUMN polyline');
    }
}