<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Gear\CustomGear\CustomGear;
use App\Domain\Strava\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

trait ProvideGearRepositoryHelpers
{
    abstract protected function getConnection(): Connection;

    public function save(Gear $gear, GearType $gearType): void
    {
        $sql = 'REPLACE INTO Gear (gearId, createdOn, distanceInMeter, name, isRetired, `type`)
        VALUES (:gearId, :createdOn, :distanceInMeter, :name, :isRetired, :type)';

        $this->getConnection()->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'distanceInMeter' => $gear->getDistance()->toMeter()->toInt(),
            'name' => $gear->getOriginalName(),
            'isRetired' => (int) $gear->isRetired(),
            'type' => $gearType->value,
        ]);
    }

    public function findAll(GearType $gearType): Gears
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->andWhere('type = :type')
            ->setParameter('type', $gearType->value)
            ->addOrderBy('isRetired', 'ASC')
            ->addOrderBy('distanceInMeter', 'DESC');

        return Gears::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ImportedGear|CustomGear
    {
        $gearType = GearType::from($result['type']);
        $args = [
            'gearId' => GearId::fromString($result['gearId']),
            'distanceInMeter' => Meter::from($result['distanceInMeter']),
            'createdOn' => SerializableDateTime::fromString($result['createdOn']),
            'name' => $result['name'],
            'isRetired' => (bool) $result['isRetired'],
        ];

        return match ($gearType) {
            GearType::IMPORTED => ImportedGear::fromState(...$args),
            GearType::CUSTOM => CustomGear::fromState(...$args),
        };
    }
}
