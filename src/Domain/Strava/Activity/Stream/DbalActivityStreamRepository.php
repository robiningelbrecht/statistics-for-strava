<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalActivityStreamRepository extends DbalRepository implements ActivityStreamRepository
{
    public function add(ActivityStream $stream): void
    {
        $sql = 'INSERT INTO ActivityStream (activityId, streamType, data, createdOn, bestAverages, normalizedPower)
        VALUES (:activityId, :streamType, :data, :createdOn, :bestAverages, :normalizedPower)';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'data' => Json::encode($stream->getData()),
            'createdOn' => $stream->getCreatedOn(),
            'bestAverages' => !empty($stream->getBestAverages()) ? Json::encode($stream->getBestAverages()) : null,
            'normalizedPower' => $stream->getNormalizedPower(),
        ]);
    }

    public function update(ActivityStream $stream): void
    {
        $sql = 'UPDATE ActivityStream 
        SET bestAverages = :bestAverages, 
            normalizedPower = :normalizedPower
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'bestAverages' => Json::encode($stream->getBestAverages()),
            'normalizedPower' => $stream->getNormalizedPower(),
        ]);
    }

    public function delete(ActivityStream $stream): void
    {
        $sql = 'DELETE FROM ActivityStream
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
        ]);
    }

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return !empty($queryBuilder->executeQuery()->fetchOne());
    }

    public function findByStreamType(StreamType $streamType): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findActivityIdsByStreamType(StreamType $streamType): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return ActivityIds::fromArray(array_map(
            fn (string $activityId) => ActivityId::fromString($activityId),
            $queryBuilder->executeQuery()->fetchFirstColumn()
        ));
    }

    public function findOneByActivityAndStreamType(ActivityId $activityId, StreamType $streamType): ActivityStream
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('ActivityStream %s-%s not found', $activityId, $streamType->value));
        }

        return $this->hydrate($result);
    }

    public function findByActivityId(ActivityId $activityId): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithoutBestAverages(int $limit): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('bestAverages IS NULL')
            ->orderBy('activityId')
            ->setMaxResults($limit);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithoutNormalizedPower(int $limit): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('normalizedPower IS NULL')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', StreamType::WATTS->value)
            ->orderBy('activityId')
            ->setMaxResults($limit);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ActivityStream
    {
        return ActivityStream::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            streamType: StreamType::from($result['streamType']),
            streamData: Json::decode($result['data']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            bestAverages: Json::decode($result['bestAverages'] ?? '[]'),
            normalizedPower: $result['normalizedPower'] ?? null
        );
    }
}
