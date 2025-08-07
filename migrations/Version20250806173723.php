<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806173723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE SegmentEffort ADD COLUMN averageHeartRate INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE SegmentEffort ADD COLUMN maxHeartRate INTEGER DEFAULT NULL');

        $results = $this->connection->executeQuery("
         SELECT 
            JSON_EXTRACT(value, '$.id') as segmentEffortId,
            JSON_EXTRACT(value, '$.average_heartrate') as averageHeartRate,
            JSON_EXTRACT(value, '$.max_heartrate') as maxHeartRate
            FROM Activity, JSON_EACH(Activity.data, '$.segment_efforts')
            WHERE averageHeartRate IS NOT NULL
        ")->fetchAllAssociative();

        foreach ($results as $result) {
            $segmentEffortId = SegmentEffortId::fromUnprefixed((string) $result['segmentEffortId']);
            $averageHeartRate = (int) $result['averageHeartRate'];
            $maxHeartRate = (int) $result['maxHeartRate'];

            $this->addSql('UPDATE SegmentEffort 
                    SET averageHeartRate = :averageHeartRate, maxHeartRate = :maxHeartRate
                    WHERE segmentEffortId = :segmentEffortId',
                [
                    'segmentEffortId' => (string) $segmentEffortId,
                    'averageHeartRate' => $averageHeartRate,
                    'maxHeartRate' => $maxHeartRate,
                ]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
