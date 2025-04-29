<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250323102519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ActivityBestEffort (activityId VARCHAR(255) NOT NULL, distanceInMeter INTEGER NOT NULL, sportType VARCHAR(255) NOT NULL, timeInSeconds INTEGER NOT NULL, PRIMARY KEY(activityId, distanceInMeter))');
        $this->addSql('CREATE INDEX ActivityBestEffort_sportTypeIndex ON ActivityBestEffort (sportType)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ActivityBestEffort');
    }
}
