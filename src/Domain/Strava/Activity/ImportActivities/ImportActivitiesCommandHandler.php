<?php

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityVisibility;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypesToImport;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Geocoding\Nominatim\Nominatim;
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
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        if (!$this->stravaDataImportStatus->gearImportIsCompleted()) {
            $command->getOutput()->writeln('<error>Not all gear has been imported yet, activities cannot be imported</error>');

            return;
        }

        $allGears = $this->gearRepository->findAll();
        $allActivityIds = $this->activityRepository->findActivityIds();
        $activityIdsToDelete = array_combine(
            $allActivityIds->map(fn (ActivityId $activityId) => (string) $activityId),
            $allActivityIds->toArray(),
        );
        $stravaActivities = $this->strava->getActivities();

        $command->getOutput()->writeln(
            sprintf('Status: %d out of %d activities imported', count($allActivityIds), count($stravaActivities))
        );

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
                    );

                if (array_key_exists('commute', $stravaActivity)) {
                    $activity->updateCommute($stravaActivity['commute']);
                }

                if (!$activity->getLocation() && $sportType->supportsReverseGeocoding()
                    && $activity->getStartingCoordinate()) {
                    $reverseGeocodedAddress = $this->nominatim->reverseGeocode($activity->getStartingCoordinate());
                    $activity->updateLocation($reverseGeocodedAddress);
                }

                try {
                    if (0 === $activity->getTotalImageCount() && ($stravaActivity['total_photo_count'] ?? 0) > 0) {
                        // Activity got updated and images were uploaded, import them.
                        if ($fileSystemPaths = $this->activityImageDownloader->downloadImages($activity->getId())) {
                            $activity->updateLocalImagePaths(array_map(
                                fn (string $fileSystemPath) => 'files/'.$fileSystemPath,
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
                    '  => Updated activity "%s - %s"',
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
                                fn (string $fileSystemPath) => 'files/'.$fileSystemPath,
                                $fileSystemPaths
                            ));
                        }
                    }

                    if ($sportType->supportsWeather() && $activity->getStartingCoordinate()) {
                        $weather = Weather::fromRawData(
                            $this->openMeteo->getWeatherStats(
                                coordinate: $activity->getStartingCoordinate(),
                                date: $activity->getStartDate()
                            ),
                            on: $activity->getStartDate()
                        );
                        $activity->updateWeather($weather);
                    }

                    if ($sportType->supportsReverseGeocoding() && $activity->getStartingCoordinate()) {
                        $reverseGeocodedAddress = $this->nominatim->reverseGeocode($activity->getStartingCoordinate());
                        $activity->updateLocation($reverseGeocodedAddress);
                    }

                    $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
                        activity: $activity,
                        rawData: $rawStravaData
                    ));
                    unset($activityIdsToDelete[(string) $activity->getId()]);

                    $command->getOutput()->writeln(sprintf(
                        '  => Imported activity "%s - %s"',
                        $activity->getName(),
                        $activity->getStartDate()->format('d-m-Y'))
                    );

                    $this->numberOfNewActivitiesToProcessPerImport->increaseNumberOfProcessedActivities();
                    if ($this->numberOfNewActivitiesToProcessPerImport->maxNumberProcessed()) {
                        // Stop importing activities, we reached the max number to process for this batch.
                        break;
                    }
                } catch (ClientException|RequestException $exception) {
                    if (!$exception->getResponse()) {
                        // Re-throw, we only want to catch supported error codes.
                        throw $exception;
                    }

                    if (429 === $exception->getResponse()->getStatusCode()) {
                        // This will allow initial imports with a lot of activities to proceed the next day.
                        // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                        $command->getOutput()->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');

                        return;
                    }

                    $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));

                    return;
                }
            }
        }

        if ($this->numberOfNewActivitiesToProcessPerImport->maxNumberProcessed()) {
            // Shortcut the process here to make sure no activities are deleted yet.
            return;
        }
        if (empty($activityIdsToDelete)) {
            return;
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
