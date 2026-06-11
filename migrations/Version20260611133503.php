<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611133503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__Activity AS SELECT activityId, startDateTime, data, gearId, weather, routeGeography, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, startingCoordinateLatitude, startingCoordinateLongitude, isCommute, streamsAreImported, workoutType, activityType, worldType, markedForDeletion, kilojoules, elapsedTimeInSeconds, importSource, externalReferenceId FROM Activity');
        $this->addSql('DROP TABLE Activity');
        $this->addSql('CREATE TABLE Activity (activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL, data CLOB DEFAULT NULL, gearId VARCHAR(255) DEFAULT NULL, weather CLOB DEFAULT NULL, routeGeography CLOB DEFAULT NULL, sportType VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, isCommute BOOLEAN DEFAULT NULL, streamsAreImported BOOLEAN DEFAULT NULL, workoutType VARCHAR(255) DEFAULT NULL, activityType VARCHAR(255) DEFAULT NULL, worldType VARCHAR(255) DEFAULT NULL, markedForDeletion BOOLEAN DEFAULT NULL, kilojoules INTEGER DEFAULT NULL, elapsedTimeInSeconds INTEGER NOT NULL, importSource VARCHAR(255) NOT NULL, externalReferenceId VARCHAR(255) DEFAULT NULL, PRIMARY KEY (activityId))');
        $this->addSql('INSERT INTO Activity (activityId, startDateTime, data, gearId, weather, routeGeography, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, startingCoordinateLatitude, startingCoordinateLongitude, isCommute, streamsAreImported, workoutType, activityType, worldType, markedForDeletion, kilojoules, elapsedTimeInSeconds, importSource, externalReferenceId) SELECT activityId, startDateTime, data, gearId, weather, routeGeography, sportType, name, description, distance, elevation, calories, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, startingCoordinateLatitude, startingCoordinateLongitude, isCommute, streamsAreImported, workoutType, activityType, worldType, markedForDeletion, kilojoules, elapsedTimeInSeconds, importSource, externalReferenceId FROM __temp__Activity');
        $this->addSql('DROP TABLE __temp__Activity');
        $this->addSql('CREATE INDEX Activity_streamsAreImported ON Activity (streamsAreImported)');
        $this->addSql('CREATE INDEX Activity_markedForDeletion ON Activity (markedForDeletion)');
        $this->addSql('CREATE INDEX Activity_gearId ON Activity (gearId)');
        $this->addSql('CREATE INDEX Activity_sportType ON Activity (sportType)');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivityBestEffort AS SELECT activityId, distanceInMeter, sportType, timeInSeconds FROM ActivityBestEffort');
        $this->addSql('DROP TABLE ActivityBestEffort');
        $this->addSql('CREATE TABLE ActivityBestEffort (activityId VARCHAR(255) NOT NULL, distanceInMeter INTEGER NOT NULL, sportType VARCHAR(255) NOT NULL, timeInSeconds INTEGER NOT NULL, PRIMARY KEY (activityId, distanceInMeter))');
        $this->addSql('INSERT INTO ActivityBestEffort (activityId, distanceInMeter, sportType, timeInSeconds) SELECT activityId, distanceInMeter, sportType, timeInSeconds FROM __temp__ActivityBestEffort');
        $this->addSql('DROP TABLE __temp__ActivityBestEffort');
        $this->addSql('CREATE INDEX ActivityBestEffort_sportTypeIndex ON ActivityBestEffort (sportType)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivitySplit AS SELECT activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds, elevationDifference, averageSpeed, minAverageSpeed, maxAverageSpeed, paceZone, gapPaceInSecondsPerKm FROM ActivitySplit');
        $this->addSql('DROP TABLE ActivitySplit');
        $this->addSql('CREATE TABLE ActivitySplit (activityId VARCHAR(255) NOT NULL, unitSystem VARCHAR(255) NOT NULL, splitNumber INTEGER NOT NULL, distance INTEGER NOT NULL, elapsedTimeInSeconds INTEGER NOT NULL, movingTimeInSeconds INTEGER NOT NULL, elevationDifference INTEGER NOT NULL, averageSpeed DOUBLE PRECISION NOT NULL, minAverageSpeed DOUBLE PRECISION NOT NULL, maxAverageSpeed INTEGER NOT NULL, paceZone INTEGER NOT NULL, gapPaceInSecondsPerKm DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (activityId, unitSystem, splitNumber))');
        $this->addSql('INSERT INTO ActivitySplit (activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds, elevationDifference, averageSpeed, minAverageSpeed, maxAverageSpeed, paceZone, gapPaceInSecondsPerKm) SELECT activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds, elevationDifference, averageSpeed, minAverageSpeed, maxAverageSpeed, paceZone, gapPaceInSecondsPerKm FROM __temp__ActivitySplit');
        $this->addSql('DROP TABLE __temp__ActivitySplit');
        $this->addSql('CREATE INDEX ActivitySplit_activityIdUnitSystemIndex ON ActivitySplit (activityId, unitSystem)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivityStream AS SELECT activityId, streamType, createdOn, dataSize, data FROM ActivityStream');
        $this->addSql('DROP TABLE ActivityStream');
        $this->addSql('CREATE TABLE ActivityStream (activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL, dataSize INTEGER NOT NULL, data BLOB NOT NULL, PRIMARY KEY (activityId, streamType))');
        $this->addSql('INSERT INTO ActivityStream (activityId, streamType, createdOn, dataSize, data) SELECT activityId, streamType, createdOn, dataSize, data FROM __temp__ActivityStream');
        $this->addSql('DROP TABLE __temp__ActivityStream');
        $this->addSql('CREATE INDEX ActivityStream_streamTypeIndex ON ActivityStream (streamType)');
        $this->addSql('CREATE INDEX ActivityStream_activityIndex ON ActivityStream (activityId)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__Challenge AS SELECT challengeId, createdOn, name, logoUrl, localLogoUrl, slug FROM Challenge');
        $this->addSql('DROP TABLE Challenge');
        $this->addSql('CREATE TABLE Challenge (challengeId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL, name VARCHAR(255) NOT NULL, logoUrl VARCHAR(255) DEFAULT NULL, localLogoUrl VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY (challengeId))');
        $this->addSql('INSERT INTO Challenge (challengeId, createdOn, name, logoUrl, localLogoUrl, slug) SELECT challengeId, createdOn, name, logoUrl, localLogoUrl, slug FROM __temp__Challenge');
        $this->addSql('DROP TABLE __temp__Challenge');
        $this->addSql('CREATE INDEX Challenge_createdOnIndex ON Challenge (createdOn)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ChatMessage AS SELECT messageId, message, messageRole, "on" FROM ChatMessage');
        $this->addSql('DROP TABLE ChatMessage');
        $this->addSql('CREATE TABLE ChatMessage (messageId VARCHAR(255) NOT NULL, message CLOB NOT NULL, messageRole VARCHAR(255) NOT NULL, "on" DATETIME NOT NULL, PRIMARY KEY (messageId))');
        $this->addSql('INSERT INTO ChatMessage (messageId, message, messageRole, "on") SELECT messageId, message, messageRole, "on" FROM __temp__ChatMessage');
        $this->addSql('DROP TABLE __temp__ChatMessage');
        $this->addSql('CREATE INDEX ChatMessage_on ON ChatMessage ("on")');
        $this->addSql('CREATE TEMPORARY TABLE __temp__Gear AS SELECT gearId, createdOn, distanceInMeter, name, isRetired, type FROM Gear');
        $this->addSql('DROP TABLE Gear');
        $this->addSql('CREATE TABLE Gear (gearId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL, distanceInMeter INTEGER NOT NULL, name VARCHAR(255) NOT NULL, isRetired BOOLEAN NOT NULL, type VARCHAR(255) DEFAULT \'imported\' NOT NULL, PRIMARY KEY (gearId))');
        $this->addSql('INSERT INTO Gear (gearId, createdOn, distanceInMeter, name, isRetired, type) SELECT gearId, createdOn, distanceInMeter, name, isRetired, type FROM __temp__Gear');
        $this->addSql('DROP TABLE __temp__Gear');
        $this->addSql('CREATE INDEX Gear_type ON Gear (type)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__SegmentEffort AS SELECT segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts, averageHeartRate, maxHeartRate FROM SegmentEffort');
        $this->addSql('DROP TABLE SegmentEffort');
        $this->addSql('CREATE TABLE SegmentEffort (segmentEffortId VARCHAR(255) NOT NULL, segmentId VARCHAR(255) NOT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL, name VARCHAR(255) NOT NULL, elapsedTimeInSeconds DOUBLE PRECISION NOT NULL, distance INTEGER NOT NULL, averageWatts DOUBLE PRECISION DEFAULT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, PRIMARY KEY (segmentEffortId))');
        $this->addSql('INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts, averageHeartRate, maxHeartRate) SELECT segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts, averageHeartRate, maxHeartRate FROM __temp__SegmentEffort');
        $this->addSql('DROP TABLE __temp__SegmentEffort');
        $this->addSql('CREATE INDEX SegmentEffort_segmentStartDateTime ON SegmentEffort (segmentId, startDateTime)');
        $this->addSql('CREATE INDEX SegmentEffort_segmentElapsedTime ON SegmentEffort (segmentId, elapsedTimeInSeconds)');
        $this->addSql('CREATE INDEX SegmentEffort_activityIndex ON SegmentEffort (activityId)');
        $this->addSql('CREATE INDEX SegmentEffort_segmentIndex ON SegmentEffort (segmentId)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__Activity AS SELECT activityType, data, streamsAreImported, markedForDeletion, activityId, startDateTime, sportType, worldType, importSource, externalReferenceId, name, description, distance, elevation, calories, kilojoules, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, elapsedTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, routeGeography, weather, gearId, isCommute, workoutType, startingCoordinateLatitude, startingCoordinateLongitude FROM Activity');
        $this->addSql('DROP TABLE Activity');
        $this->addSql('CREATE TABLE Activity (activityType VARCHAR(255) DEFAULT NULL, data CLOB DEFAULT NULL --(DC2Type:json)
        , streamsAreImported BOOLEAN DEFAULT NULL, markedForDeletion BOOLEAN DEFAULT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , sportType VARCHAR(255) NOT NULL, worldType VARCHAR(255) DEFAULT NULL, importSource VARCHAR(255) DEFAULT \'stravaApi\' NOT NULL, externalReferenceId VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, kilojoules INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, elapsedTimeInSeconds INTEGER DEFAULT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, routeGeography CLOB DEFAULT NULL --(DC2Type:json)
        , weather CLOB DEFAULT NULL --(DC2Type:json)
        , gearId VARCHAR(255) DEFAULT NULL, isCommute BOOLEAN DEFAULT NULL, workoutType VARCHAR(255) DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (activityId))');
        $this->addSql('INSERT INTO Activity (activityType, data, streamsAreImported, markedForDeletion, activityId, startDateTime, sportType, worldType, importSource, externalReferenceId, name, description, distance, elevation, calories, kilojoules, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, elapsedTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, routeGeography, weather, gearId, isCommute, workoutType, startingCoordinateLatitude, startingCoordinateLongitude) SELECT activityType, data, streamsAreImported, markedForDeletion, activityId, startDateTime, sportType, worldType, importSource, externalReferenceId, name, description, distance, elevation, calories, kilojoules, averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate, averageCadence, movingTimeInSeconds, elapsedTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths, polyline, routeGeography, weather, gearId, isCommute, workoutType, startingCoordinateLatitude, startingCoordinateLongitude FROM __temp__Activity');
        $this->addSql('DROP TABLE __temp__Activity');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');
        $this->addSql('CREATE INDEX Activity_sportType ON Activity (sportType)');
        $this->addSql('CREATE INDEX Activity_gearId ON Activity (gearId)');
        $this->addSql('CREATE INDEX Activity_markedForDeletion ON Activity (markedForDeletion)');
        $this->addSql('CREATE INDEX Activity_streamsAreImported ON Activity (streamsAreImported)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivityBestEffort AS SELECT activityId, distanceInMeter, sportType, timeInSeconds FROM ActivityBestEffort');
        $this->addSql('DROP TABLE ActivityBestEffort');
        $this->addSql('CREATE TABLE ActivityBestEffort (activityId VARCHAR(255) NOT NULL, distanceInMeter INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sportType VARCHAR(255) NOT NULL, timeInSeconds INTEGER NOT NULL)');
        $this->addSql('INSERT INTO ActivityBestEffort (activityId, distanceInMeter, sportType, timeInSeconds) SELECT activityId, distanceInMeter, sportType, timeInSeconds FROM __temp__ActivityBestEffort');
        $this->addSql('DROP TABLE __temp__ActivityBestEffort');
        $this->addSql('CREATE INDEX ActivityBestEffort_sportTypeIndex ON ActivityBestEffort (sportType)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivitySplit AS SELECT activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds, elevationDifference, averageSpeed, minAverageSpeed, maxAverageSpeed, paceZone, gapPaceInSecondsPerKm FROM ActivitySplit');
        $this->addSql('DROP TABLE ActivitySplit');
        $this->addSql('CREATE TABLE ActivitySplit (activityId VARCHAR(255) NOT NULL, unitSystem VARCHAR(255) NOT NULL, splitNumber INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, distance INTEGER NOT NULL, elapsedTimeInSeconds INTEGER NOT NULL, movingTimeInSeconds INTEGER NOT NULL, elevationDifference INTEGER NOT NULL, averageSpeed DOUBLE PRECISION NOT NULL, minAverageSpeed DOUBLE PRECISION NOT NULL, maxAverageSpeed INTEGER NOT NULL, paceZone INTEGER NOT NULL, gapPaceInSecondsPerKm DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO ActivitySplit (activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds, elevationDifference, averageSpeed, minAverageSpeed, maxAverageSpeed, paceZone, gapPaceInSecondsPerKm) SELECT activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds, elevationDifference, averageSpeed, minAverageSpeed, maxAverageSpeed, paceZone, gapPaceInSecondsPerKm FROM __temp__ActivitySplit');
        $this->addSql('DROP TABLE __temp__ActivitySplit');
        $this->addSql('CREATE INDEX ActivitySplit_activityIdUnitSystemIndex ON ActivitySplit (activityId, unitSystem)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ActivityStream AS SELECT dataSize, activityId, streamType, createdOn, data FROM ActivityStream');
        $this->addSql('DROP TABLE ActivityStream');
        $this->addSql('CREATE TABLE ActivityStream (dataSize INTEGER DEFAULT 0 NOT NULL, activityId VARCHAR(255) NOT NULL, streamType VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , data BLOB DEFAULT NULL, PRIMARY KEY (activityId, streamType))');
        $this->addSql('INSERT INTO ActivityStream (dataSize, activityId, streamType, createdOn, data) SELECT dataSize, activityId, streamType, createdOn, data FROM __temp__ActivityStream');
        $this->addSql('DROP TABLE __temp__ActivityStream');
        $this->addSql('CREATE INDEX ActivityStream_activityIndex ON ActivityStream (activityId)');
        $this->addSql('CREATE INDEX ActivityStream_streamTypeIndex ON ActivityStream (streamType)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__Challenge AS SELECT challengeId, createdOn, name, logoUrl, localLogoUrl, slug FROM Challenge');
        $this->addSql('DROP TABLE Challenge');
        $this->addSql('CREATE TABLE Challenge (challengeId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , name VARCHAR(255) NOT NULL, logoUrl VARCHAR(255) DEFAULT NULL, localLogoUrl VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY (challengeId))');
        $this->addSql('INSERT INTO Challenge (challengeId, createdOn, name, logoUrl, localLogoUrl, slug) SELECT challengeId, createdOn, name, logoUrl, localLogoUrl, slug FROM __temp__Challenge');
        $this->addSql('DROP TABLE __temp__Challenge');
        $this->addSql('CREATE INDEX Challenge_createdOnIndex ON Challenge (createdOn)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ChatMessage AS SELECT messageId, message, messageRole, "on" FROM ChatMessage');
        $this->addSql('DROP TABLE ChatMessage');
        $this->addSql('CREATE TABLE ChatMessage (messageId VARCHAR(255) NOT NULL, message CLOB NOT NULL, messageRole VARCHAR(255) NOT NULL, "on" DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY (messageId))');
        $this->addSql('INSERT INTO ChatMessage (messageId, message, messageRole, "on") SELECT messageId, message, messageRole, "on" FROM __temp__ChatMessage');
        $this->addSql('DROP TABLE __temp__ChatMessage');
        $this->addSql('CREATE INDEX ChatMessage_on ON ChatMessage ("on")');
        $this->addSql('CREATE TEMPORARY TABLE __temp__Gear AS SELECT gearId, createdOn, distanceInMeter, name, isRetired, type FROM Gear');
        $this->addSql('DROP TABLE Gear');
        $this->addSql('CREATE TABLE Gear (gearId VARCHAR(255) NOT NULL, createdOn DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , distanceInMeter INTEGER NOT NULL, name VARCHAR(255) NOT NULL, isRetired BOOLEAN NOT NULL, type VARCHAR(255) DEFAULT \'imported\' NOT NULL, PRIMARY KEY (gearId))');
        $this->addSql('INSERT INTO Gear (gearId, createdOn, distanceInMeter, name, isRetired, type) SELECT gearId, createdOn, distanceInMeter, name, isRetired, type FROM __temp__Gear');
        $this->addSql('DROP TABLE __temp__Gear');
        $this->addSql('CREATE INDEX Gear_type ON Gear (type)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__SegmentEffort AS SELECT segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts, averageHeartRate, maxHeartRate FROM SegmentEffort');
        $this->addSql('DROP TABLE SegmentEffort');
        $this->addSql('CREATE TABLE SegmentEffort (segmentEffortId VARCHAR(255) NOT NULL, segmentId VARCHAR(255) NOT NULL, activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , name VARCHAR(255) NOT NULL, elapsedTimeInSeconds DOUBLE PRECISION NOT NULL, distance INTEGER NOT NULL, averageWatts DOUBLE PRECISION DEFAULT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, PRIMARY KEY (segmentEffortId))');
        $this->addSql('INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts, averageHeartRate, maxHeartRate) SELECT segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts, averageHeartRate, maxHeartRate FROM __temp__SegmentEffort');
        $this->addSql('DROP TABLE __temp__SegmentEffort');
        $this->addSql('CREATE INDEX SegmentEffort_segmentIndex ON SegmentEffort (segmentId)');
        $this->addSql('CREATE INDEX SegmentEffort_activityIndex ON SegmentEffort (activityId)');
        $this->addSql('CREATE INDEX SegmentEffort_segmentElapsedTime ON SegmentEffort (segmentId, elapsedTimeInSeconds)');
        $this->addSql('CREATE INDEX SegmentEffort_segmentStartDateTime ON SegmentEffort (segmentId, startDateTime)');
    }
}
