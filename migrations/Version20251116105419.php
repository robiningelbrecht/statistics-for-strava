<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116105419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivityStream AS SELECT activityId, streamType, createdOn, data, bestAverages, normalizedPower, valueDistribution FROM ActivityStream');
        $this->addSql('DROP TABLE ActivityStream');
        $this->addSql('CREATE TABLE ActivityStream (activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , data CLOB NOT NULL --(DC2Type:json)
        , bestAverages CLOB DEFAULT NULL --(DC2Type:json)
        , normalizedPower INTEGER DEFAULT NULL, valueDistribution CLOB DEFAULT NULL --(DC2Type:json)
        , computedFieldsState CLOB DEFAULT NULL --(DC2Type:json)
        , PRIMARY KEY(activityId, streamType))');
        $this->addSql('INSERT INTO ActivityStream (activityId, streamType, createdOn, data, bestAverages, normalizedPower, valueDistribution) SELECT activityId, streamType, createdOn, data, bestAverages, normalizedPower, valueDistribution FROM __temp__ActivityStream');
        $this->addSql('DROP TABLE __temp__ActivityStream');
        $this->addSql('CREATE INDEX ActivityStream_activityIndex ON ActivityStream (activityId)');
        $this->addSql('CREATE INDEX ActivityStream_streamTypeIndex ON ActivityStream (streamType)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
