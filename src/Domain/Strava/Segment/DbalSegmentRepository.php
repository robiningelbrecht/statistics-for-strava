<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\String\Name;

final readonly class DbalSegmentRepository extends DbalRepository implements SegmentRepository
{
    public function add(Segment $segment): void
    {
        $sql = 'INSERT INTO Segment (segmentId, name, sportType, distance, maxGradient, isFavourite, deviceName, climbCategory, countryCode) 
                VALUES (:segmentId, :name, :sportType, :distance, :maxGradient, :isFavourite, :deviceName, :climbCategory, :countryCode)';

        $this->connection->executeStatement($sql, [
            'segmentId' => $segment->getId(),
            'name' => $segment->getOriginalName(),
            'sportType' => $segment->getSportType()->value,
            'distance' => $segment->getDistance()->toMeter()->toInt(),
            'maxGradient' => $segment->getMaxGradient(),
            'isFavourite' => (int) $segment->isFavourite(),
            'deviceName' => $segment->getDeviceName(),
            'climbCategory' => $segment->getClimbCategory(),
            'countryCode' => $segment->getCountryCode(),
        ]);
    }

    public function update(Segment $segment): void
    {
        $sql = 'UPDATE Segment SET 
                    isFavourite = :isFavourite   
                    WHERE segmentId = :segmentId';

        $this->connection->executeStatement($sql, [
            'segmentId' => $segment->getId(),
            'isFavourite' => (int) $segment->isFavourite(),
        ]);
    }

    public function find(SegmentId $segmentId): Segment
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Segment')
            ->andWhere('segmentId = :segmentId')
            ->setParameter('segmentId', $segmentId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Segment "%s" not found', $segmentId));
        }

        return $this->hydrate($result);
    }

    public function findAll(Pagination $pagination): Segments
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*', '(SELECT COUNT(*) FROM SegmentEffort WHERE SegmentEffort.segmentId = Segment.segmentId) as countCompleted')
            ->from('Segment')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->orderBy('countCompleted', 'DESC');

        return Segments::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function count(): int
    {
        return (int) $this->connection->executeQuery('SELECT COUNT(*) FROM Segment')->fetchOne();
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): Segment
    {
        return Segment::fromState(
            segmentId: SegmentId::fromString($result['segmentId']),
            name: Name::fromString($result['name']),
            sportType: SportType::from($result['sportType']),
            distance: Meter::from($result['distance'])->toKilometer(),
            maxGradient: $result['maxGradient'],
            isFavourite: (bool) $result['isFavourite'],
            climbCategory: $result['climbCategory'],
            deviceName: $result['deviceName'],
            countryCode: $result['countryCode']
        );
    }

    public function deleteOrphaned(): void
    {
        $this->connection->executeStatement('DELETE FROM Segment WHERE NOT EXISTS(
            SELECT 1 FROM SegmentEffort WHERE SegmentEffort.segmentId = Segment.segmentId
        )');
    }
}
