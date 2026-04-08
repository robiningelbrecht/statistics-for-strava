<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Domain\Activity\Stream\Metric\ActivityStreamMetricType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408083118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM ActivityStreamMetric WHERE metricType = :metricType', [
            'metricType' => ActivityStreamMetricType::VALUE_DISTRIBUTION->value,
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
