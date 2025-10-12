<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\WorldType;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012151344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs.
        $this->addSql('ALTER TABLE Activity ADD COLUMN worldType VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE Activity set worldType = :worldType', [
            'worldType' => WorldType::REAL_WORLD->value,
        ]);
        $this->addSql('UPDATE Activity set worldType = :worldType WHERE LOWER(deviceName) = :deviceName', [
            'worldType' => WorldType::ZWIFT->value,
            'deviceName' => 'zwift',
        ]);
        $this->addSql('UPDATE Activity set worldType = :worldType WHERE LOWER(deviceName) = :deviceName', [
            'worldType' => WorldType::ROUVY->value,
            'deviceName' => 'rouvy',
        ]);

        $activityIds = $this->connection->fetchFirstColumn(
            <<<'SQL'
                SELECT activityId FROM Activity WHERE activityType = :activityType
            SQL,
            [
                'activityType' => ActivityType::RUN->value,
            ]
        );

        if (0 === count($activityIds)) {
            return;
        }

        $this->addSql(<<<'SQL'
            DELETE FROM CombinedActivityStream
            WHERE activityId IN(:activityIds)
        SQL,
            [
                'activityIds' => $activityIds,
            ],
            [
                'activityIds' => ArrayParameterType::STRING,
            ],
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
