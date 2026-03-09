<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Serialization\Json;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309114023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Segment ADD COLUMN averageGradient DOUBLE PRECISION DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $averageGrades = [];

        $result = $this->connection->executeQuery(
            "SELECT data FROM Activity WHERE data IS NOT NULL AND JSON_EXTRACT(data, '$.segment_efforts') IS NOT NULL"
        );

        while ($row = $result->fetchAssociative()) {
            $data = Json::decode($row['data']);
            foreach ($data['segment_efforts'] ?? [] as $effort) {
                $segmentId = 'segment-'.$effort['segment']['id'];
                if (isset($averageGrades[$segmentId])) {
                    continue;
                }
                if (!isset($effort['segment']['average_grade'])) {
                    continue;
                }
                $averageGrades[$segmentId] = $effort['segment']['average_grade'];
            }
        }

        foreach ($averageGrades as $segmentId => $averageGrade) {
            $this->connection->executeStatement(
                'UPDATE Segment SET averageGradient = :averageGradient WHERE segmentId = :segmentId',
                [
                    'averageGradient' => $averageGrade,
                    'segmentId' => $segmentId,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
