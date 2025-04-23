<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations\MigrateToVersion20250118164026;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use Doctrine\DBAL\Connection;

final readonly class MigrateToVersion20250118164026CommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private GearRepository $gearRepository,
        private Connection $connection,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof MigrateToVersion20250118164026);

        $allGear = $this->gearRepository->findAll();

        foreach ($this->activityRepository->findActivityIds() as $activityId) {
            /** @var array<mixed> $result */
            $result = $this->connection->executeQuery('SELECT * FROM Activity WHERE activityId = :activityId', [
                'activityId' => $activityId,
            ])->fetchAssociative();

            $rawData = Json::decode($result['data']);
            $location = Json::decode($result['location'] ?? '[]');
            $gear = null;
            if ($gearId = GearId::fromOptionalString($result['gearId'])) {
                $gear = $allGear->getByGearId($gearId);
            }

            $activity = Activity::createFromRawData(
                rawData: $rawData,
                gearId: $gearId,
                gearName: $gear?->getName()
            );
            $activity->updateWeather(Weather::fromRawData(
                Json::decode($result['weather'] ?? '[]'),
                $activity->getStartDate()
            ));
            $activity->updateLocation($location ? Location::fromState($location) : null);
            $activity->updateLocalImagePaths($rawData['localImagePaths'] ?? []);

            $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
                activity: $activity,
                rawData: $rawData
            ));
        }
    }
}
