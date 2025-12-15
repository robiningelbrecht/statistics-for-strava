<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\WorkoutType;
use App\Domain\Gear\GearId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class InitializeActivity implements ActivityImportStep
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $activityId = $context->getActivityId();
        $rawStravaData = $context->getRawStravaData();

        $sportType = SportType::from($rawStravaData['sport_type']);

        try {
            $activity = $this->activityRepository->find($activityId);
            $gearId = GearId::fromOptionalUnprefixed($rawStravaData['gear_id'] ?? null);

            $activity
                ->updateName($rawStravaData['name'])
                ->updateSportType($sportType)
                ->updateDistance(Kilometer::from(round($rawStravaData['distance'] / 1000, 3)))
                ->updateAverageSpeed(MetersPerSecond::from($rawStravaData['average_speed'])->toKmPerHour())
                ->updateMaxSpeed(MetersPerSecond::from($rawStravaData['max_speed'])->toKmPerHour())
                ->updateMovingTimeInSeconds($rawStravaData['moving_time'] ?? 0)
                ->updateElevation(Meter::from($rawStravaData['total_elevation_gain']))
                ->updateKudoCount($rawStravaData['kudos_count'] ?? 0)
                ->updateStartingCoordinate(Coordinate::createFromOptionalLatAndLng(
                    Latitude::fromOptionalString($rawStravaData['start_latlng'][0] ?? null),
                    Longitude::fromOptionalString($rawStravaData['start_latlng'][1] ?? null),
                ))
                ->updatePolyline($rawStravaData['map']['summary_polyline'] ?? null)
                ->updateGear($gearId)
                ->updateWorkoutType(WorkoutType::fromStravaInt($rawStravaData['workout_type'] ?? null));

            if (array_key_exists('commute', $rawStravaData)) {
                $activity->updateCommute($rawStravaData['commute']);
            }

            return $context
                ->withActivity($activity);
        } catch (EntityNotFound) {
        }

        $activity = Activity::createFromRawData($rawStravaData);

        return $context
            ->withActivity($activity);
    }
}
