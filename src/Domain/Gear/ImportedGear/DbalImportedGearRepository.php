<?php

declare(strict_types=1);

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Activity\ActivityIds;
use App\Domain\Gear\CustomGear\CustomGear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Gears;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ProvideGearRepositoryHelpers;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class DbalImportedGearRepository extends DbalRepository implements ImportedGearRepository
{
    use ProvideGearRepositoryHelpers {
        save as protected parentSave;
        findAll as protected parentFindAll;
        findAllUsed as protected parentFindAllUsed;
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    public function save(ImportedGear $gear): void
    {
        if ($gear instanceof CustomGear) {
            throw new \InvalidArgumentException(sprintf('Cannot save %s as ImportedGear', $gear::class));
        }

        $this->parentSave(
            gear: $gear,
            gearType: GearType::IMPORTED
        );
    }

    public function findAll(): Gears
    {
        return $this->parentFindAll(
            gearType: GearType::IMPORTED
        );
    }

    public function findAllUsed(): Gears
    {
        return $this->parentFindAllUsed(
            gearType: GearType::IMPORTED
        );
    }

    public function find(GearId $gearId): ImportedGear
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('Gear.*', 'SUM(Activity.distance) AS totalDistance')
            ->from('Gear')
            ->leftJoin('Gear', 'Activity', 'Activity', 'Activity.gearId = Gear.gearId')
            ->andWhere('Gear.gearId = :gearId')
            ->setParameter('gearId', $gearId)
            ->andWhere('Gear.type = :type')
            ->setParameter('type', GearType::IMPORTED->value)
            ->groupBy('Gear.gearId');

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Gear "%s" not found', $gearId));
        }

        return $this->hydrate($result);
    }

    public function findUniqueStravaGearIds(?ActivityIds $restrictToActivityIds): GearIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('DISTINCT JSON_EXTRACT(data, "$.gear_id") as stravaGearId')
            ->from('Activity')
            ->andWhere('stravaGearId IS NOT NULL');

        if ($restrictToActivityIds && !$restrictToActivityIds->isEmpty()) {
            $queryBuilder->andWhere('activityId IN (:activityIds)');
            $queryBuilder->setParameter(
                key: 'activityIds',
                value: array_map(strval(...), $restrictToActivityIds->toArray()),
                type: ArrayParameterType::STRING
            );
        }

        return GearIds::fromArray(array_map(
            GearId::fromUnprefixed(...),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }
}
