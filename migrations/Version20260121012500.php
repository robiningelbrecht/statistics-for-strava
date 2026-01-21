<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121012500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add minMovingTimeInSeconds and maxMovingTimeInSeconds to ActivityLap';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DELETE FROM ActivityLap
        SQL);
        $this->addSql('ALTER TABLE ActivityLap ADD COLUMN minMovingTimeInSeconds INTEGER NOT NULL');
        $this->addSql('ALTER TABLE ActivityLap ADD COLUMN maxMovingTimeInSeconds INTEGER NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
