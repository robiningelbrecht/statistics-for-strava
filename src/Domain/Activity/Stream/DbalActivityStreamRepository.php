<?php

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;

final readonly class DbalActivityStreamRepository extends DbalRepository implements ActivityStreamRepository
{
    public function add(ActivityStream $stream): void
    {
        $sql = 'INSERT INTO ActivityStream (activityId, streamType, data, createdOn, bestAverages, normalizedPower, valueDistribution)
        VALUES (:activityId, :streamType, :data, :createdOn, :bestAverages, :normalizedPower, :valueDistribution)';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'data' => Json::encode($stream->getData()),
            'createdOn' => $stream->getCreatedOn(),
            'bestAverages' => !empty($stream->getBestAverages()) ? Json::encode($stream->getBestAverages()) : null,
            'valueDistribution' => !empty($stream->getValueDistribution()) ? Json::encode($stream->getValueDistribution()) : null,
            'normalizedPower' => $stream->getNormalizedPower(),
        ]);
    }

    public function update(ActivityStream $stream): void
    {
        $sql = 'UPDATE ActivityStream 
        SET bestAverages = :bestAverages, 
            normalizedPower = :normalizedPower,
            valueDistribution = :valueDistribution
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'bestAverages' => Json::encode($stream->getBestAverages()),
            'valueDistribution' => Json::encode($stream->getValueDistribution()),
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
            $this->hydrate(...),
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
            ActivityId::fromString(...),
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
            $this->hydrate(...),
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
            $this->hydrate(...),
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
            $this->hydrate(...),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithoutDistributionValues(int $limit): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('valueDistribution IS NULL')
            ->andWhere('streamType IN(:streamTypes)')
            ->setParameter('streamTypes', [
                StreamType::WATTS->value,
                StreamType::HEART_RATE->value,
                StreamType::VELOCITY->value,
            ], ArrayParameterType::STRING)
            ->orderBy('activityId')
            ->setMaxResults($limit);

        return ActivityStreams::fromArray(array_map(
            $this->hydrate(...),
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
            valueDistribution: Json::decode($result['valueDistribution'] ?? '[]'),
            bestAverages: Json::decode($result['bestAverages'] ?? '[]'),
            normalizedPower: $result['normalizedPower'] ?? null
        );
    }
}
