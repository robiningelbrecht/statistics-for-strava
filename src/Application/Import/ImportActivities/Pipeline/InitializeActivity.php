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

            $activity = $activity
                ->withName($rawStravaData['name'])
                ->withSportType($sportType)
                ->withDistance(Kilometer::from(round($rawStravaData['distance'] / 1000, 3)))
                ->withAverageSpeed(MetersPerSecond::from($rawStravaData['average_speed'])->toKmPerHour())
                ->withMaxSpeed(MetersPerSecond::from($rawStravaData['max_speed'])->toKmPerHour())
                ->withMovingTimeInSeconds($rawStravaData['moving_time'] ?? 0)
                ->withElevation(Meter::from($rawStravaData['total_elevation_gain']))
                ->withKudoCount($rawStravaData['kudos_count'] ?? 0)
                ->withStartingCoordinate(Coordinate::createFromOptionalLatAndLng(
                    Latitude::fromOptionalString($rawStravaData['start_latlng'][0] ?? null),
                    Longitude::fromOptionalString($rawStravaData['start_latlng'][1] ?? null),
                ))
                ->withPolyline($rawStravaData['map']['summary_polyline'] ?? null)
                ->withGear($gearId)
                ->withWorkoutType(WorkoutType::fromStravaInt($rawStravaData['workout_type'] ?? null));

            if (array_key_exists('commute', $rawStravaData)) {
                $activity = $activity->withCommute($rawStravaData['commute']);
            }

            return $context->withActivity($activity);
        } catch (EntityNotFound) {
        }

        $activity = Activity::createFromRawData($rawStravaData);

        return $context->withActivity($activity);
    }
}
