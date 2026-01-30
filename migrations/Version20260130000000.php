<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Squashed migration containing the full database schema.
 * This replaces all migrations up to and including Version20260128120916.
 */
final class Version20260130000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Activity (activityType VARCHAR(255) DEFAULT NULL, data CLOB DEFAULT NULL, streamsAreImported BOOLEAN DEFAULT NULL, markedForDeletion BOOLEAN DEFAULT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL, sportType VARCHAR(255) NOT NULL, worldType VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, routeGeography CLOB DEFAULT NULL, weather CLOB DEFAULT NULL, gearId VARCHAR(255) DEFAULT NULL, isCommute BOOLEAN DEFAULT NULL, workoutType VARCHAR(255) DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (activityId))');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');
        $this->addSql('CREATE INDEX Activity_sportType ON Activity (sportType)');
        $this->addSql('CREATE TABLE ActivityBestEffort (activityId VARCHAR(255) NOT NULL, distanceInMeter INTEGER NOT NULL, sportType VARCHAR(255) NOT NULL, timeInSeconds INTEGER NOT NULL, PRIMARY KEY (activityId, distanceInMeter))');
        $this->addSql('CREATE INDEX ActivityBestEffort_sportTypeIndex ON ActivityBestEffort (sportType)');
        $this->addSql('CREATE TABLE ActivityLap (lapId VARCHAR(255) NOT NULL, activityId VARCHAR(255) NOT NULL, lapNumber INTEGER NOT NULL, name VARCHAR(255) NOT NULL, elapsedTimeInSeconds INTEGER NOT NULL, movingTimeInSeconds INTEGER NOT NULL, distance INTEGER NOT NULL, averageSpeed DOUBLE PRECISION NOT NULL, minAverageSpeed DOUBLE PRECISION NOT NULL, maxAverageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, elevationDifference INTEGER NOT NULL, averageHeartRate INTEGER DEFAULT NULL, PRIMARY KEY (lapId))');
        $this->addSql('CREATE INDEX ActivitySplit_activityId ON ActivityLap (activityId)');
        $this->addSql('CREATE TABLE ActivitySplit (activityId VARCHAR(255) NOT NULL, unitSystem VARCHAR(255) NOT NULL, splitNumber INTEGER NOT NULL, distance INTEGER NOT NULL, elapsedTimeInSeconds INTEGER NOT NULL, movingTimeInSeconds INTEGER NOT NULL, elevationDifference INTEGER NOT NULL, averageSpeed DOUBLE PRECISION NOT NULL, minAverageSpeed DOUBLE PRECISION NOT NULL, maxAverageSpeed INTEGER NOT NULL, paceZone INTEGER NOT NULL, PRIMARY KEY (activityId, unitSystem, splitNumber))');
        $this->addSql('CREATE INDEX ActivitySplit_activityIdUnitSystemIndex ON ActivitySplit (activityId, unitSystem)');
        $this->addSql('CREATE TABLE ActivityStream (activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL, data CLOB NOT NULL, computedFieldsState CLOB DEFAULT NULL, normalizedPower INTEGER DEFAULT NULL, valueDistribution CLOB DEFAULT NULL, bestAverages CLOB DEFAULT NULL, PRIMARY KEY (activityId, streamType))');
        $this->addSql('CREATE INDEX ActivityStream_activityIndex ON ActivityStream (activityId)');
        $this->addSql('CREATE INDEX ActivityStream_streamTypeIndex ON ActivityStream (streamType)');
        $this->addSql('CREATE TABLE Challenge (challengeId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL, name VARCHAR(255) NOT NULL, logoUrl VARCHAR(255) DEFAULT NULL, localLogoUrl VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY (challengeId))');
        $this->addSql('CREATE INDEX Challenge_createdOnIndex ON Challenge (createdOn)');
        $this->addSql('CREATE TABLE ChatMessage (messageId VARCHAR(255) NOT NULL, message CLOB NOT NULL, messageRole VARCHAR(255) NOT NULL, "on" DATETIME NOT NULL, PRIMARY KEY (messageId))');
        $this->addSql('CREATE TABLE CombinedActivityStream (activityId VARCHAR(255) NOT NULL, unitSystem VARCHAR(255) NOT NULL, streamTypes VARCHAR(255) NOT NULL, data CLOB NOT NULL, PRIMARY KEY (activityId, unitSystem))');
        $this->addSql('CREATE TABLE Gear (gearId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL, distanceInMeter INTEGER NOT NULL, name VARCHAR(255) NOT NULL, isRetired BOOLEAN NOT NULL, type VARCHAR(255) DEFAULT \'imported\' NOT NULL, PRIMARY KEY (gearId))');
        $this->addSql('CREATE TABLE KeyValue ("key" VARCHAR(255) NOT NULL, value CLOB NOT NULL, PRIMARY KEY ("key"))');
        $this->addSql('CREATE TABLE Segment (segmentId VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, sportType VARCHAR(255) NOT NULL, distance INTEGER NOT NULL, maxGradient DOUBLE PRECISION NOT NULL, isFavourite BOOLEAN NOT NULL, climbCategory INTEGER DEFAULT NULL, deviceName VARCHAR(255) DEFAULT NULL, countryCode VARCHAR(255) DEFAULT NULL, detailsHaveBeenImported BOOLEAN DEFAULT NULL, polyline CLOB DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (segmentId))');
        $this->addSql('CREATE TABLE SegmentEffort (segmentEffortId VARCHAR(255) NOT NULL, segmentId VARCHAR(255) NOT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL, name VARCHAR(255) NOT NULL, elapsedTimeInSeconds DOUBLE PRECISION NOT NULL, distance INTEGER NOT NULL, averageWatts DOUBLE PRECISION DEFAULT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, PRIMARY KEY (segmentEffortId))');
        $this->addSql('CREATE INDEX SegmentEffort_segmentIndex ON SegmentEffort (segmentId)');
        $this->addSql('CREATE INDEX SegmentEffort_activityIndex ON SegmentEffort (activityId)');
        $this->addSql('CREATE TABLE WebhookEvent (objectId VARCHAR(255) NOT NULL, objectType VARCHAR(255) NOT NULL, aspectType VARCHAR(255) NOT NULL, payload CLOB NOT NULL, PRIMARY KEY (objectId))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE Activity');
        $this->addSql('DROP TABLE ActivityBestEffort');
        $this->addSql('DROP TABLE ActivityLap');
        $this->addSql('DROP TABLE ActivitySplit');
        $this->addSql('DROP TABLE ActivityStream');
        $this->addSql('DROP TABLE Challenge');
        $this->addSql('DROP TABLE ChatMessage');
        $this->addSql('DROP TABLE CombinedActivityStream');
        $this->addSql('DROP TABLE Gear');
        $this->addSql('DROP TABLE KeyValue');
        $this->addSql('DROP TABLE Segment');
        $this->addSql('DROP TABLE SegmentEffort');
        $this->addSql('DROP TABLE WebhookEvent');
    }
}
