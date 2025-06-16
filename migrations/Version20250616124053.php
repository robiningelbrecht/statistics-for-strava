<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616124053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ActivityLap (lapId VARCHAR(255) NOT NULL, activityId VARCHAR(255) NOT NULL, lapNumber INTEGER NOT NULL, name VARCHAR(255) NOT NULL, elapsedTimeInSeconds INTEGER NOT NULL, movingTimeInSeconds INTEGER NOT NULL, distance INTEGER NOT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, elevationDifference INTEGER NOT NULL, averageHeartRate INTEGER DEFAULT NULL, PRIMARY KEY(lapId))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX ActivitySplit_activityId ON ActivityLap (activityId)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE ActivityLap
        SQL);
    }
}
