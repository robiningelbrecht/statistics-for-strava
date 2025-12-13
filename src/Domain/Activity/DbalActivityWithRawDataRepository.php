<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityWithRawDataRepository extends DbalRepository implements ActivityWithRawDataRepository
{
    public function __construct(
        Connection $connection,
        private ActivityRepository $activityRepository,
    ) {
        parent::__construct($connection);
    }

    public function find(ActivityId $activityId): ActivityWithRawData
    {
        $activity = $this->activityRepository->find($activityId);

        $data = $this->connection->executeQuery('SELECT data FROM Activity WHERE activityId = :activityId', [
            'activityId' => $activityId,
        ])->fetchOne();

        return ActivityWithRawData::fromState(
            activity: $activity,
            rawData: Json::decode($data),
        );
    }

    public function add(ActivityWithRawData $activityWithRawData): void
    {
        $sql = 'INSERT INTO Activity (
            activityId, startDateTime, sportType, activityType, worldType, name, description, distance,
            elevation, startingCoordinateLatitude, startingCoordinateLongitude, calories,
            averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate,
            averageCadence,movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths,
            polyline, routeGeography, weather, gearId, data, isCommute, streamsAreImported, workoutType
        ) VALUES(
            :activityId, :startDateTime, :sportType, :activityType, :worldType, :name, :description, :distance,
            :elevation, :startingCoordinateLatitude, :startingCoordinateLongitude, :calories,
            :averagePower, :maxPower, :averageSpeed, :maxSpeed, :averageHeartRate, :maxHeartRate,
            :averageCadence, :movingTimeInSeconds, :kudoCount, :deviceName, :totalImageCount, :localImagePaths,
            :polyline, :routeGeography, :weather, :gearId, :data, :isCommute, :streamsAreImported, :workoutType
        )';

        $activity = $activityWithRawData->getActivity();
        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'sportType' => $activity->getSportType()->value,
            'worldType' => $activity->getWorldType()->value,
            'activityType' => $activity->getSportType()->getActivityType()->value,
            'name' => $activity->getOriginalName(),
            'description' => $activity->getDescription(),
            'distance' => $activity->getDistance()->toMeter()->toInt(),
            'elevation' => $activity->getElevation()->toInt(),
            'startingCoordinateLatitude' => $activity->getStartingCoordinate()?->getLatitude()->toFloat(),
            'startingCoordinateLongitude' => $activity->getStartingCoordinate()?->getLongitude()->toFloat(),
            'calories' => $activity->getCalories(),
            'averagePower' => $activity->getAveragePower(),
            'maxPower' => $activity->getMaxPower(),
            'averageSpeed' => $activity->getAverageSpeed()->toFloat(),
            'maxSpeed' => $activity->getMaxSpeed()->toFloat(),
            'averageHeartRate' => $activity->getAverageHeartRate(),
            'maxHeartRate' => $activity->getMaxHeartRate(),
            'averageCadence' => $activity->getAverageCadence(),
            'movingTimeInSeconds' => $activity->getMovingTimeInSeconds(),
            'kudoCount' => $activity->getKudoCount(),
            'deviceName' => $activity->getDeviceName(),
            'totalImageCount' => $activity->getTotalImageCount(),
            'localImagePaths' => implode(',', $activity->getLocalImagePaths()),
            'polyline' => $activity->getPolyline(),
            'routeGeography' => Json::encode($activity->getRouteGeography()),
            'weather' => $activity->getWeather() ? Json::encode($activity->getWeather()) : null,
            'gearId' => $activity->getGearId(),
            'data' => Json::encode($this->cleanData($activityWithRawData->getRawData())),
            'isCommute' => (int) $activity->isCommute(),
            'streamsAreImported' => 0,
            'workoutType' => $activity->getWorkoutType()?->value,
        ]);
    }

    public function update(ActivityWithRawData $activityWithRawData): void
    {
        $sql = 'UPDATE Activity SET 
                    name = :name, 
                    sportType = :sportType, 
                    activityType = :activityType, 
                    distance = :distance, 
                    averageSpeed = :averageSpeed,
                    maxSpeed = :maxSpeed,
                    movingTimeInSeconds = :movingTimeInSeconds,
                    elevation = :elevation,
                    kudoCount = :kudoCount,
                    polyline = :polyline,
                    startingCoordinateLatitude = :startingCoordinateLatitude,
                    startingCoordinateLongitude = :startingCoordinateLongitude,
                    routeGeography = :routeGeography,
                    gearId = :gearId, 
                    totalImageCount = :totalImageCount,
                    localImagePaths = :localImagePaths,
                    data = :data,
                    isCommute = :isCommute,
                    workoutType = :workoutType    
                    WHERE activityId = :activityId';

        $activity = $activityWithRawData->getActivity();
        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'sportType' => $activity->getSportType()->value,
            'activityType' => $activity->getSportType()->getActivityType()->value,
            'name' => $activity->getOriginalName(),
            'distance' => $activity->getDistance()->toMeter()->toInt(),
            'elevation' => $activity->getElevation()->toInt(),
            'averageSpeed' => $activity->getAverageSpeed()->toFloat(),
            'maxSpeed' => $activity->getMaxSpeed()->toFloat(),
            'movingTimeInSeconds' => $activity->getMovingTimeInSeconds(),
            'kudoCount' => $activity->getKudoCount(),
            'polyline' => $activity->getPolyline(),
            'startingCoordinateLatitude' => $activity->getStartingCoordinate()?->getLatitude()->toFloat(),
            'startingCoordinateLongitude' => $activity->getStartingCoordinate()?->getLongitude()->toFloat(),
            'routeGeography' => Json::encode($activity->getRouteGeography()),
            'gearId' => $activity->getGearId(),
            'totalImageCount' => $activity->getTotalImageCount(),
            'localImagePaths' => implode(',', $activity->getLocalImagePaths()),
            'isCommute' => (int) $activity->isCommute(),
            'workoutType' => $activity->getWorkoutType()?->value,
            'data' => Json::encode($this->cleanData($activityWithRawData->getRawData())),
        ]);
    }

    public function markActivityStreamsAsImported(ActivityId $activityId): void
    {
        $sql = 'UPDATE Activity SET streamsAreImported = 1 WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityId,
        ]);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function cleanData(array $data): array
    {
        if (isset($data['map']['polyline'])) {
            unset($data['map']['polyline']);
        }

        return $data;
    }
}
