<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714071904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Activity ADD COLUMN activityType VARCHAR(255) DEFAULT NULL');
        foreach (ActivityType::cases() as $activityType) {
            $this->addSql(
                'UPDATE Activity SET activityType = :activityType WHERE sportType IN (:sportTypes)',
                [
                    'activityType' => $activityType->value,
                    'sportTypes' => $activityType->getSportTypes()->map(fn (SportType $sportType) => $sportType->value),
                ],
                [
                    'sportTypes' => ArrayParameterType::STRING,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__Activity AS SELECT data, streamsAreImported, activityId, startDateTime, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, location, weather, gearId, gearName, isCommute, workoutType, startingCoordinateLatitude, startingCoordinateLongitude FROM Activity');
        $this->addSql('DROP TABLE Activity');
        $this->addSql('CREATE TABLE Activity (data CLOB DEFAULT NULL --(DC2Type:json)
        , streamsAreImported BOOLEAN DEFAULT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , sportType VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, location CLOB DEFAULT NULL --(DC2Type:json)
        , weather CLOB DEFAULT NULL --(DC2Type:json)
        , gearId VARCHAR(255) DEFAULT NULL, gearName VARCHAR(255) DEFAULT NULL, isCommute BOOLEAN DEFAULT NULL, workoutType VARCHAR(255) DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(activityId))');
        $this->addSql('INSERT INTO Activity (data, streamsAreImported, activityId, startDateTime, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, location, weather, gearId, gearName, isCommute, workoutType, startingCoordinateLatitude, startingCoordinateLongitude) SELECT data, streamsAreImported, activityId, startDateTime, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, location, weather, gearId, gearName, isCommute, workoutType, startingCoordinateLatitude, startingCoordinateLongitude FROM __temp__Activity');
        $this->addSql('DROP TABLE __temp__Activity');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');
        $this->addSql('CREATE INDEX Activity_sportType ON Activity (sportType)');
    }
}
