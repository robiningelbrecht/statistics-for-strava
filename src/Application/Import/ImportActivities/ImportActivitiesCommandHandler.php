<?php

namespace App\Application\Import\ImportActivities;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityVisibility;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypesToImport;
use App\Domain\Activity\WorkoutType;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Integration\Geocoding\Nominatim\CouldNotReverseGeocodeAddress;
use App\Domain\Integration\Geocoding\Nominatim\Location;
use App\Domain\Integration\Geocoding\Nominatim\Nominatim;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteo;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteoArchiveApiCallHasFailed;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteoForecastApiCallHasFailed;
use App\Domain\Integration\Weather\OpenMeteo\Weather;
use App\Domain\Strava\RateLimit\StravaRateLimitHasBeenReached;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

#[WithMutex(lockName: 'importDataOrBuildApp')]
final readonly class ImportActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private OpenMeteo $openMeteo,
        private Nominatim $nominatim,
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private GearRepository $gearRepository,
        private SportTypesToImport $sportTypesToImport,
        private ActivityVisibilitiesToImport $activityVisibilitiesToImport,
        private ActivitiesToSkipDuringImport $activitiesToSkipDuringImport,
        private ?SkipActivitiesRecordedBefore $skipActivitiesRecordedBefore,
        private StravaDataImportStatus $stravaDataImportStatus,
        private NumberOfNewActivitiesToProcessPerImport $numberOfNewActivitiesToProcessPerImport,
        private ActivityImageDownloader $activityImageDownloader,
        private Mutex $mutex,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        $this->strava->setConsoleOutput($command->getOutput());

        if (!$this->stravaDataImportStatus->gearImportIsCompleted()) {
            $command->getOutput()->writeln('<error>Not all gear has been imported yet, activities cannot be imported</error>');

            return;
        }

        $allGears = $this->gearRepository->findAll();
        $allActivityIds = $this->activityRepository->findActivityIds();
        $activityIdsToDelete = array_combine(
            $allActivityIds->map(fn (ActivityId $activityId): string => (string) $activityId),
            $allActivityIds->toArray(),
        );
        $stravaActivities = $this->strava->getActivities();
        $countTotalStravaActivities = count($stravaActivities);

        $command->getOutput()->writeln(
            sprintf('Status: %d out of %d activities imported', count($allActivityIds), $countTotalStravaActivities)
        );

        $delta = 1;
        foreach ($stravaActivities as $stravaActivity) {
            if (!$sportType = SportType::tryFrom($stravaActivity['sport_type'])) {
                $command->getOutput()->writeln(sprintf(
                    '  => Sport type "%s" not supported yet. <a href="https://github.com/robiningelbrecht/statistics-for-strava/issues/new?assignees=robiningelbrecht&labels=new+feature&projects=&template=feature_request.md&title=Add+support+for+sport+type+%s>Open a new GitHub issue</a> to if you want support for this sport type',
                    $stravaActivity['sport_type'],
                    $stravaActivity['sport_type']));
                continue;
            }
            if (!$this->sportTypesToImport->has($sportType)) {
                continue;
            }
            $activityVisibility = ActivityVisibility::from($stravaActivity['visibility']);
            if (!$this->activityVisibilitiesToImport->has($activityVisibility)) {
                continue;
            }

            if ($this->skipActivitiesRecordedBefore?->isAfterOrOn(SerializableDateTime::createFromFormat(
                format: Activity::DATE_TIME_FORMAT,
                datetime: $stravaActivity['start_date_local'],
                timezone: SerializableTimezone::default(),
            ))) {
                continue;
            }

            $activityId = ActivityId::fromUnprefixed((string) $stravaActivity['id']);
            if ($this->activitiesToSkipDuringImport->has($activityId)) {
                continue;
            }
            try {
                $activityWithRawData = $this->activityWithRawDataRepository->find($activityId);
                $activity = $activityWithRawData->getActivity();
                $gearId = GearId::fromOptionalUnprefixed($stravaActivity['gear_id'] ?? null);

                $activity
                    ->updateName($stravaActivity['name'])
                    ->updateSportType($sportType)
                    ->updateDistance(Kilometer::from(round($stravaActivity['distance'] / 1000, 3)))
                    ->updateAverageSpeed(MetersPerSecond::from($stravaActivity['average_speed'])->toKmPerHour())
                    ->updateMaxSpeed(MetersPerSecond::from($stravaActivity['max_speed'])->toKmPerHour())
                    ->updateMovingTimeInSeconds($stravaActivity['moving_time'] ?? 0)
                    ->updateElevation(Meter::from($stravaActivity['total_elevation_gain']))
                    ->updateKudoCount($stravaActivity['kudos_count'] ?? 0)
                    ->updateStartingCoordinate(Coordinate::createFromOptionalLatAndLng(
                        Latitude::fromOptionalString($stravaActivity['start_latlng'][0] ?? null),
                        Longitude::fromOptionalString($stravaActivity['start_latlng'][1] ?? null),
                    ))
                    ->updatePolyline($stravaActivity['map']['summary_polyline'] ?? null)
                    ->updateGear(
                        $gearId,
                        $gearId ? $allGears->getByGearId($gearId)?->getName() : null
                    )
                    ->updateWorkoutType(WorkoutType::fromStravaInt($stravaActivity['workout_type'] ?? null));

                if (array_key_exists('commute', $stravaActivity)) {
                    $activity->updateCommute($stravaActivity['commute']);
                }

                if (!$activity->getLocation() && $activity->getStartingCoordinate()) {
                    if ($sportType->supportsReverseGeocoding()) {
                        try {
                            $reverseGeocodedAddress = $this->nominatim->reverseGeocode($activity->getStartingCoordinate());
                            $activity->updateLocation($reverseGeocodedAddress);
                        } catch (CouldNotReverseGeocodeAddress) {
                        }
                    } elseif ($activity->isZwiftRide() && ($zwiftMap = $activity->getLeafletMap())) {
                        $location = Location::create([
                            'state' => $zwiftMap->getLabel(),
                        ]);
                        $activity->updateLocation($location);
                    }
                }

                try {
                    if (!$newTotalImageCount = ($stravaActivity['total_photo_count'] ?? 0)) {
                        // New image count is 0, remove all images.
                        $activity->updateLocalImagePaths([]);
                    }
                    if ($activity->getTotalImageCount() !== $newTotalImageCount && $newTotalImageCount > 0) {
                        // Activity got updated and images were uploaded, import them.
                        if ($fileSystemPaths = $this->activityImageDownloader->downloadImages($activity->getId())) {
                            $activity->updateLocalImagePaths(array_map(
                                fn (string $fileSystemPath): string => 'files/'.$fileSystemPath,
                                $fileSystemPaths
                            ));
                        }
                    }
                } catch (ClientException|RequestException) {
                }

                $this->activityWithRawDataRepository->update(ActivityWithRawData::fromState(
                    activity: $activity,
                    rawData: [
                        ...$activityWithRawData->getRawData(),
                        ...$stravaActivity,
                    ]
                ));
                unset($activityIdsToDelete[(string) $activity->getId()]);

                $command->getOutput()->writeln(sprintf(
                    '  => [%d/%d] Updated activity: "%s - %s"',
                    $delta,
                    $countTotalStravaActivities,
                    $activity->getName(),
                    $activity->getStartDate()->format('d-m-Y'))
                );
            } catch (EntityNotFound) {
                try {
                    $rawStravaData = $this->strava->getActivity($activityId);
                    $gearId = GearId::fromOptionalUnprefixed($stravaActivity['gear_id'] ?? null);
                    $activity = Activity::createFromRawData(
                        rawData: $rawStravaData,
                        gearId: $gearId,
                        gearName: $gearId ? $allGears->getByGearId($gearId)?->getName() : null
                    );

                    if (($rawStravaData['total_photo_count'] ?? 0) > 0) {
                        if ($fileSystemPaths = $this->activityImageDownloader->downloadImages($activity->getId())) {
                            $activity->updateLocalImagePaths(array_map(
                                fn (string $fileSystemPath): string => 'files/'.$fileSystemPath,
                                $fileSystemPaths
                            ));
                        }
                    }

                    if ($sportType->supportsWeather() && $activity->getStartingCoordinate()) {
                        try {
                            $weather = Weather::fromRawData(
                                $this->openMeteo->getWeatherStats(
                                    coordinate: $activity->getStartingCoordinate(),
                                    date: $activity->getStartDate()
                                ),
                                on: $activity->getStartDate()
                            );
                            $activity->updateWeather($weather);
                        } catch (OpenMeteoForecastApiCallHasFailed|OpenMeteoArchiveApiCallHasFailed) {
                        }
                    }

                    if ($activity->getStartingCoordinate()) {
                        if ($sportType->supportsReverseGeocoding()) {
                            try {
                                $reverseGeocodedAddress = $this->nominatim->reverseGeocode($activity->getStartingCoordinate());
                                $activity->updateLocation($reverseGeocodedAddress);
                            } catch (CouldNotReverseGeocodeAddress) {
                            }
                        } elseif ($activity->isZwiftRide() && ($zwiftMap = $activity->getLeafletMap())) {
                            $location = Location::create([
                                'state' => $zwiftMap->getLabel(),
                            ]);
                            $activity->updateLocation($location);
                        }
                    }

                    $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
                        activity: $activity,
                        rawData: $rawStravaData
                    ));
                    unset($activityIdsToDelete[(string) $activity->getId()]);

                    $command->getOutput()->writeln(sprintf(
                        '  => [%d/%d] Imported activity: "%s - %s"',
                        $delta,
                        $countTotalStravaActivities,
                        $activity->getName(),
                        $activity->getStartDate()->format('d-m-Y'))
                    );

                    $this->numberOfNewActivitiesToProcessPerImport->increaseNumberOfProcessedActivities();
                    if ($this->numberOfNewActivitiesToProcessPerImport->maxNumberProcessed()) {
                        // Stop importing activities, we reached the max number to process for this batch.
                        break;
                    }
                } catch (StravaRateLimitHasBeenReached $exception) {
                    $command->getOutput()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

                    return;
                } catch (ClientException|RequestException $exception) {
                    $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));

                    return;
                }
            }

            $this->mutex->heartbeat();
            ++$delta;
        }

        if ($this->numberOfNewActivitiesToProcessPerImport->maxNumberProcessed()) {
            // Shortcut the process here to make sure no activities are deleted yet.
            return;
        }

        if (count($activityIdsToDelete) === count($allActivityIds)) {
            throw new \RuntimeException('All activities appear to be marked for deletion. This seems like a configuration issue. Aborting to prevent data loss');
        }

        foreach ($activityIdsToDelete as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $activity->delete();
            $this->activityRepository->delete($activity);

            $command->getOutput()->writeln(sprintf(
                '  => Deleted activity "%s - %s"',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'))
            );
        }
    }
}
