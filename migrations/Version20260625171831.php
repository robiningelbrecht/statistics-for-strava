<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Serialization\Json;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Yaml\Yaml;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625171831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $gearMaintenanceConfigFile = dirname(__DIR__).'/config/app/gear-maintenance.yaml';

        $this->skipIf(
            !file_exists($gearMaintenanceConfigFile),
            'No gear-maintenance.yaml found, nothing to migrate'
        );

        $config = Yaml::parseFile($gearMaintenanceConfigFile);

        $this->connection->executeStatement(
            'REPLACE INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            [
                'key' => 'gearMaintenance',
                'value' => Json::encode($config),
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM KeyValue WHERE `key` = :key', ['key' => 'gearMaintenance']);
    }
}
