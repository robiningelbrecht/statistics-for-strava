<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250704073735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__Activity AS SELECT activityId, startDateTime, data, gearId, weather, location, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, gearName, startingCoordinateLatitude, startingCoordinateLongitude, isCommute, streamsAreImported, workoutType FROM Activity');
        $this->addSql('DROP TABLE Activity');
        $this->addSql('CREATE TABLE Activity (activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , data CLOB DEFAULT NULL --(DC2Type:json)
        , gearId VARCHAR(255) DEFAULT NULL, weather CLOB DEFAULT NULL --(DC2Type:json)
        , location CLOB DEFAULT NULL --(DC2Type:json)
        , sportType VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, gearName VARCHAR(255) DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, isCommute BOOLEAN DEFAULT NULL, streamsAreImported BOOLEAN DEFAULT NULL, workoutType VARCHAR(255) DEFAULT NULL, PRIMARY KEY(activityId))');
        $this->addSql('INSERT INTO Activity (activityId, startDateTime, data, gearId, weather, location, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, gearName, startingCoordinateLatitude, startingCoordinateLongitude, isCommute, streamsAreImported, workoutType) SELECT activityId, startDateTime, data, gearId, weather, location, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, gearName, startingCoordinateLatitude, startingCoordinateLongitude, isCommute, streamsAreImported, workoutType FROM __temp__Activity');
        $this->addSql('DROP TABLE __temp__Activity');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');
        $this->addSql('CREATE INDEX Activity_sportType ON Activity (sportType)');
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
    }
}
