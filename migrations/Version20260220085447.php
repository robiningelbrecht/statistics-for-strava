<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\ValueObject\String\CompressedString;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220085447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ActivityStream');

        if (!$table->hasColumn('dataSize')) {
            $this->addSql('ALTER TABLE ActivityStream ADD COLUMN dataSize INTEGER NOT NULL DEFAULT 0');
            $this->addSql('UPDATE ActivityStream SET dataSize = json_array_length(data)');
        }

        if (!$table->hasColumn('data_compressed')) {
            $this->addSql('ALTER TABLE ActivityStream ADD COLUMN data_compressed BLOB DEFAULT NULL');
        }
    }

    public function postUp(Schema $schema): void
    {
        $result = $this->connection->executeQuery('SELECT activityId, streamType, data FROM ActivityStream');
        while ($row = $result->fetchAssociative()) {
            $compressed = CompressedString::fromUncompressed($row['data']);

            $this->connection->executeStatement(
                'UPDATE ActivityStream SET data_compressed = :compressed WHERE activityId = :activityId AND streamType = :streamType',
                [
                    'compressed' => (string) $compressed,
                    'activityId' => $row['activityId'],
                    'streamType' => $row['streamType'],
                ]
            );
        }

        $this->connection->executeStatement('ALTER TABLE ActivityStream DROP COLUMN data');
        $this->connection->executeStatement('ALTER TABLE ActivityStream RENAME COLUMN data_compressed TO data');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
