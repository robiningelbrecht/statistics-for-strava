<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Doctrine\DBAL\ArrayParameterType;

final readonly class DbalCombinedActivityStreamRepository extends DbalRepository implements CombinedActivityStreamRepository
{
    public function add(CombinedActivityStream $combinedActivityStream): void
    {
        $sql = 'INSERT INTO CombinedActivityStream (activityId, unitSystem, data)
        VALUES (:activityId, :unitSystem, :data)';

        $this->connection->executeStatement($sql, [
            'activityId' => $combinedActivityStream->getActivityId(),
            'unitSystem' => $combinedActivityStream->getUnitSystem()->value,
            'data' => Json::encode($combinedActivityStream->getData()),
        ]);
    }

    public function findOneForActivityAndUnitSystem(ActivityId $activityId, UnitSystem $unitSystem): CombinedActivityStream
    {
        $sql = 'SELECT * FROM CombinedActivityStream 
                WHERE activityId = :activityId AND unitSystem = :unitSystem';
        if (!$result = $this->connection->executeQuery($sql,
            [
                'activityId' => $activityId,
                'unitSystem' => $unitSystem->value,
            ],
        )->fetchAssociative()) {
            throw new EntityNotFound('CombinedActivityStream not found');
        }

        return CombinedActivityStream::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            unitSystem: UnitSystem::from($result['unitSystem']),
            data: Json::decode($result['data'])
        );
    }

    public function findActivityIdsThatNeedStreamCombining(): ActivityIds
    {
        $sql = 'SELECT Activity.activityId FROM Activity 
                  WHERE sportType IN (:sportTypes)
                  AND NOT EXISTS (
                    SELECT 1 FROM CombinedActivityStream WHERE CombinedActivityStream.activityId = Activity.activityId
                  )
                  AND EXISTS (
                    SELECT 1 FROM ActivityStream x
                    WHERE x.activityId = Activity.activityId AND x.streamType = :altitudeStreamType AND json_array_length(x.data) > 0
                  )
                  AND EXISTS (
                    SELECT 1 FROM ActivityStream y
                    WHERE y.activityId = Activity.activityId AND y.streamType = :distanceStreamType AND json_array_length(y.data) > 0
                  )';

        return ActivityIds::fromArray(array_map(
            fn (string $activityId) => ActivityId::fromString($activityId),
            $this->connection->executeQuery($sql,
                [
                    'altitudeStreamType' => StreamType::ALTITUDE->value,
                    'distanceStreamType' => StreamType::DISTANCE->value,
                    'sportTypes' => array_map(
                        fn (SportType $sportType) => $sportType->value,
                        array_filter(SportType::cases(), fn (SportType $sportType) => $sportType->getActivityType()->supportsCombinedStreamCalculation())
                    ),
                ],
                [
                    'sportTypes' => ArrayParameterType::STRING,
                ]
            )->fetchFirstColumn()
        ));
    }
}
