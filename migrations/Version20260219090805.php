<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219090805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ActivityStreamMetric (activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, metricType VARCHAR(255) NOT NULL, data BLOB NOT NULL, PRIMARY KEY (activityId, streamType, metricType))');
        $this->addSql('CREATE INDEX ActivityStreamMetric_activityIndex ON ActivityStreamMetric (activityId)');
        $this->addSql('CREATE INDEX ActivityStreamMetric_streamTypeIndex ON ActivityStreamMetric (streamType)');
        $this->addSql('CREATE INDEX ActivityStreamMetric_metricTypeIndex ON ActivityStreamMetric (metricType)');

        $this->addSql('ALTER TABLE ActivityStream DROP COLUMN computedFieldsState');
        $this->addSql('ALTER TABLE ActivityStream DROP COLUMN normalizedPower');
        $this->addSql('ALTER TABLE ActivityStream DROP COLUMN valueDistribution');
        $this->addSql('ALTER TABLE ActivityStream DROP COLUMN bestAverages');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
