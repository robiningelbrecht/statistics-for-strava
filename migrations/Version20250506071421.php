<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Domain\Strava\Segment\SegmentId;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506071421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE Segment ADD COLUMN climbCategory INTEGER DEFAULT NULL
        SQL);

        $climbCategories = $this->connection->executeQuery(
            <<<'SQL'
            SELECT
                JSON_EXTRACT(value, '$.segment.id') as segmentId,
                JSON_EXTRACT(value, '$.segment.climb_category') as climbCategory
            FROM
                Activity,
                JSON_EACH(Activity.data, '$.segment_efforts')
            WHERE climbCategory IS NOT NULL
            GROUP BY segmentId, climbCategory
            SQL
        )->fetchAllKeyValue();

        foreach ($climbCategories as $segmentId => $climbCategory) {
            $this->addSql(
                <<<SQL
                UPDATE Segment
                SET climbCategory = :climbCategory
                WHERE segmentId = :segmentId
                SQL,
                [
                    'segmentId' => (string) SegmentId::fromUnprefixed((string) $segmentId),
                    'climbCategory' => $climbCategory,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
