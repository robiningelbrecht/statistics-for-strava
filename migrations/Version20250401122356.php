<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250401122356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE Activity ADD COLUMN workoutType VARCHAR(255) DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE Activity
            SET workoutType = 'race'
            WHERE JSON_EXTRACT(data, '$.workout_type') IN (1, 11)
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE Activity
            SET workoutType = 'workout'
            WHERE JSON_EXTRACT(data, '$.workout_type') IN (3, 12)
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE Activity
            SET workoutType = 'longRun'
            WHERE JSON_EXTRACT(data, '$.workout_type') IN (2)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
