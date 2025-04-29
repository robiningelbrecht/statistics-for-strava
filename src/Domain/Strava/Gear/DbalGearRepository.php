<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalGearRepository extends DbalRepository implements GearRepository
{
    public function save(Gear $gear): void
    {
        $sql = 'REPLACE INTO Gear (gearId, createdOn, distanceInMeter, name, isRetired)
        VALUES (:gearId, :createdOn, :distanceInMeter, :name, :isRetired)';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'distanceInMeter' => $gear->getDistance()->toMeter()->toInt(),
            'name' => $gear->getOriginalName(),
            'isRetired' => (int) $gear->isRetired(),
        ]);
    }

    public function findAll(): Gears
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->addOrderBy('isRetired', 'ASC')
            ->addOrderBy('distanceInMeter', 'DESC');

        return Gears::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function find(GearId $gearId): Gear
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->andWhere('gearId = :gearId')
            ->setParameter('gearId', $gearId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Gear "%s" not found', $gearId));
        }

        return $this->hydrate($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): Gear
    {
        return Gear::fromState(
            gearId: GearId::fromString($result['gearId']),
            distanceInMeter: Meter::from($result['distanceInMeter']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            name: $result['name'],
            isRetired: (bool) $result['isRetired']
        );
    }
}
