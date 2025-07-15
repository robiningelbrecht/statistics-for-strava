<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714144651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Segment ADD COLUMN countryCode VARCHAR(255) DEFAULT NULL');

        $this->addSql(
            <<<SQL
            UPDATE Segment
            SET countryCode = (
                SELECT JSON_EXTRACT(location, '$.country_code') as countryCode
                FROM SegmentEffort
                INNER JOIN Activity ON SegmentEffort.activityId = Activity.activityId
                WHERE JSON_EXTRACT(location, '$.country_code') IS NOT NULL
                AND segmentId = Segment.segmentId
                GROUP BY segmentId
            )
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
