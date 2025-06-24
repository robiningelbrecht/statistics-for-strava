<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250624174952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ActivityStream ADD COLUMN normalizedPower INTEGER DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__ActivityStream AS SELECT activityId, streamType, createdOn, data, bestAverages FROM ActivityStream
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ActivityStream
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE ActivityStream (activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , data CLOB NOT NULL --(DC2Type:json)
            , bestAverages CLOB DEFAULT NULL --(DC2Type:json)
            , PRIMARY KEY(activityId, streamType))
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO ActivityStream (activityId, streamType, createdOn, data, bestAverages) SELECT activityId, streamType, createdOn, data, bestAverages FROM __temp__ActivityStream
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__ActivityStream
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX ActivityStream_activityIndex ON ActivityStream (activityId)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX ActivityStream_streamTypeIndex ON ActivityStream (streamType)
        SQL);
    }
}
