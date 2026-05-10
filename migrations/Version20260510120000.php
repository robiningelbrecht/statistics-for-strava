<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ActivityStrengthSet (
            activityId VARCHAR(255) NOT NULL,
            position INTEGER NOT NULL,
            exerciseName VARCHAR(255) NOT NULL,
            numberOfSets INTEGER NOT NULL,
            numberOfReps INTEGER NOT NULL,
            weightLbs DOUBLE PRECISION DEFAULT NULL,
            estimatedOneRepMax DOUBLE PRECISION DEFAULT NULL,
            PRIMARY KEY (activityId, position)
        )');
    }

    public function postUp(Schema $schema): void
    {
        // Backfill from existing Activity.description rows.
        // Uses inline regex matching the same format as StrengthWorkoutDescriptionParser
        // to avoid coupling the migration to the domain class.
        $linePattern = '/^(?P<name>[A-Za-z][A-Za-z0-9 ]*?)\s+(?=\d+x\d+)(?P<sets>[1-9]\d*)x(?P<reps>[1-9]\d*)(?:@(?P<weight>[0-9]+(?:\.[0-9]+)?))?$/';

        $results = $this->connection->executeQuery(
            "SELECT activityId, description FROM Activity WHERE description IS NOT NULL AND description != ''"
        );

        $sql = 'INSERT INTO ActivityStrengthSet (activityId, position, exerciseName, numberOfSets, numberOfReps, weightLbs, estimatedOneRepMax)
                VALUES (:activityId, :position, :exerciseName, :numberOfSets, :numberOfReps, :weightLbs, :estimatedOneRepMax)';

        while ($row = $results->fetchAssociative()) {
            $position = 1;
            foreach (preg_split('/\r?\n/', $row['description']) ?: [] as $line) {
                $trimmed = trim($line);
                if ('' === $trimmed || !preg_match($linePattern, $trimmed, $matches)) {
                    continue;
                }

                $weightLbs = isset($matches['weight'])
                    ? (float) $matches['weight']
                    : null;
                $numberOfReps = (int) $matches['reps'];
                $estimatedOneRepMax = null !== $weightLbs
                    ? $weightLbs * (1 + $numberOfReps / 30.0)
                    : null;

                $this->connection->executeStatement($sql, [
                    'activityId' => $row['activityId'],
                    'position' => $position++,
                    'exerciseName' => $matches['name'],
                    'numberOfSets' => (int) $matches['sets'],
                    'numberOfReps' => $numberOfReps,
                    'weightLbs' => $weightLbs,
                    'estimatedOneRepMax' => $estimatedOneRepMax,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
