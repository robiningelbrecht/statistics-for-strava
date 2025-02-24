<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

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

    public function save(ActivityWithRawData $activityWithRawData): void
    {
        $sql = 'REPLACE INTO Activity (
            activityId, startDateTime, sportType, name, description, distance,
            elevation, startingCoordinateLatitude, startingCoordinateLongitude, calories,
            averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate,
            averageCadence,movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths,
            polyline, location, weather, gearId, gearName, data, isCommute
        ) VALUES(
            :activityId, :startDateTime, :sportType, :name, :description, :distance,
            :elevation, :startingCoordinateLatitude, :startingCoordinateLongitude, :calories,
            :averagePower, :maxPower, :averageSpeed, :maxSpeed, :averageHeartRate, :maxHeartRate,
            :averageCadence, :movingTimeInSeconds, :kudoCount, :deviceName, :totalImageCount, :localImagePaths,
            :polyline, :location, :weather, :gearId, :gearName, :data, :isCommute
        )';

        $activity = $activityWithRawData->getActivity();
        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'sportType' => $activity->getSportType()->value,
            'name' => $activity->getName(),
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
            'location' => $activity->getLocation() ? Json::encode($activity->getLocation()) : null,
            'weather' => $activity->getWeather() ? Json::encode($activity->getWeather()) : null,
            'gearId' => $activity->getGearId(),
            'gearName' => $activity->getGearName(),
            'data' => Json::encode($this->cleanData($activityWithRawData->getRawData())),
            'isCommute' => (int) $activity->isCommute(),
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
