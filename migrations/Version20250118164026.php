<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\Factory\CommandBusAwareMigration;
use App\Infrastructure\Doctrine\Migrations\MigrateToVersion20250118164026\MigrateToVersion20250118164026;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250118164026 extends AbstractMigration implements CommandBusAwareMigration
{
    private ?CommandBus $commandBus = null;

    public function setCommandBus(CommandBus $commandBus): void
    {
        $this->commandBus = $commandBus;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Migrate Activity table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__Activity AS SELECT activityId, startDateTime, data, gearId, weather, location, sportType FROM Activity');
        $this->addSql('DROP TABLE Activity');
        $this->addSql('CREATE TABLE Activity (activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , data CLOB DEFAULT NULL --(DC2Type:json)
        , gearId VARCHAR(255) DEFAULT NULL, weather CLOB DEFAULT NULL --(DC2Type:json)
        , location CLOB DEFAULT NULL --(DC2Type:json)
        , sportType VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, gearName VARCHAR(255) DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(activityId))');

        $this->addSql('INSERT INTO Activity (activityId, startDateTime, data, gearId, weather, location, sportType, name, description, distance, elevation, startingCoordinateLatitude, startingCoordinateLongitude, calories,
                        averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate,
                        averageCadence,movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths,
                        polyline, gearName) 
                        SELECT activityId, startDateTime, data, gearId, weather, location, sportType, "", "", 0, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 0, "", "", "" FROM __temp__Activity');
        $this->addSql('DROP TABLE __temp__Activity');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');

        // Migrate Segment table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__Segment AS SELECT segmentId, name, data FROM Segment');
        $this->addSql('DROP TABLE Segment');
        $this->addSql('CREATE TABLE Segment (segmentId VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, sportType VARCHAR(255) NOT NULL, distance INTEGER NOT NULL, maxGradient DOUBLE PRECISION NOT NULL, isFavourite BOOLEAN NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, PRIMARY KEY(segmentId))');
        $this->addSql('INSERT INTO Segment (segmentId, name, sportType, distance, maxGradient, isFavourite, deviceName) SELECT segmentId, name, JSON_EXTRACT(data, "$.sport_type"), CAST(JSON_EXTRACT(data, "$.distance") AS INTEGER), JSON_EXTRACT(data, "$.maximum_grade"), JSON_EXTRACT(data, "$.starred"), JSON_EXTRACT(data, "$.device_name") FROM __temp__Segment');
        $this->addSql('DROP TABLE __temp__Segment');

        // Migrate SegmentEffort table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__SegmentEffort AS SELECT segmentEffortId, segmentId, activityId, startDateTime, data FROM SegmentEffort');
        $this->addSql('DROP TABLE SegmentEffort');
        $this->addSql('CREATE TABLE SegmentEffort (segmentEffortId VARCHAR(255) NOT NULL, segmentId VARCHAR(255) NOT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , name VARCHAR(255) NOT NULL, elapsedTimeInSeconds DOUBLE PRECISION NOT NULL, distance INTEGER NOT NULL, averageWatts DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(segmentEffortId))');
        $this->addSql('INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts) SELECT segmentEffortId, segmentId, activityId, startDateTime, JSON_EXTRACT(data, "$.name"), JSON_EXTRACT(data, "$.elapsed_time"), CAST(JSON_EXTRACT(data, "$.distance") AS INTEGER), JSON_EXTRACT(data, "$.average_watts") FROM __temp__SegmentEffort');
        $this->addSql('DROP TABLE __temp__SegmentEffort');
        $this->addSql('CREATE INDEX SegmentEffort_segmentIndex ON SegmentEffort (segmentId)');
        $this->addSql('CREATE INDEX SegmentEffort_activityIndex ON SegmentEffort (activityId)');

        // Migrate Gear table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__Gear AS SELECT gearId, createdOn, distanceInMeter, data FROM Gear');
        $this->addSql('DROP TABLE Gear');
        $this->addSql('CREATE TABLE Gear (gearId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , distanceInMeter INTEGER NOT NULL, name VARCHAR(255) NOT NULL, isRetired BOOLEAN NOT NULL, PRIMARY KEY(gearId))');
        $this->addSql('INSERT INTO Gear (gearId, createdOn, distanceInMeter, name, isRetired) SELECT gearId, createdOn, distanceInMeter, JSON_EXTRACT(data, "$.name"), JSON_EXTRACT(data, "$.retired") FROM __temp__Gear');
        $this->addSql('DROP TABLE __temp__Gear');

        // Migrate Challenge table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__Challenge AS SELECT challengeId, createdOn, data FROM Challenge');
        $this->addSql('DROP TABLE Challenge');
        $this->addSql('CREATE TABLE Challenge (challengeId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , name VARCHAR(255) NOT NULL, logoUrl VARCHAR(255) DEFAULT NULL, localLogoUrl VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(challengeId))');
        $this->addSql('INSERT INTO Challenge (challengeId, createdOn, name, logoUrl, localLogoUrl, slug) SELECT challengeId, createdOn, JSON_EXTRACT(data, "$.name"), JSON_EXTRACT(data, "$.logo_url"), JSON_EXTRACT(data, "$.localLogo"), JSON_EXTRACT(data, "$.url") FROM __temp__Challenge');
        $this->addSql('DROP TABLE __temp__Challenge');
        $this->addSql('CREATE INDEX Challenge_createdOnIndex ON Challenge (createdOn)');

        // Migrate ActivityStream table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivityStream AS SELECT activityId, streamType, createdOn, data, bestAverages FROM ActivityStream');
        $this->addSql('DROP TABLE ActivityStream');
        $this->addSql('CREATE TABLE ActivityStream (activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , data CLOB NOT NULL --(DC2Type:json)
        , bestAverages CLOB DEFAULT NULL --(DC2Type:json)
        , PRIMARY KEY(activityId, streamType))');
        $this->addSql('INSERT INTO ActivityStream (activityId, streamType, createdOn, data, bestAverages) SELECT activityId, streamType, createdOn, data, bestAverages FROM __temp__ActivityStream');
        $this->addSql('DROP TABLE __temp__ActivityStream');
        $this->addSql('CREATE INDEX ActivityStream_activityIndex ON ActivityStream (activityId)');
        $this->addSql('CREATE INDEX ActivityStream_streamTypeIndex ON ActivityStream (streamType)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    public function postUp(Schema $schema): void
    {
        $this->commandBus->dispatch(new MigrateToVersion20250118164026());
    }
}
