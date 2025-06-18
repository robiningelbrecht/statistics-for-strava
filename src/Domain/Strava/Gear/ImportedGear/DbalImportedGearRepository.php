<?php

namespace App\Domain\Strava\Gear\ImportedGear;

use App\Domain\Strava\Gear\CustomGear\CustomGear;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\Gears;
use App\Domain\Strava\Gear\GearType;
use App\Domain\Strava\Gear\ProvideGearRepositoryHelpers;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use Doctrine\DBAL\Connection;

final readonly class DbalImportedGearRepository extends DbalRepository implements ImportedGearRepository
{
    use ProvideGearRepositoryHelpers {
        save as protected parentSave;
        findAll as protected parentFindAll;
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

    public function find(GearId $gearId): ImportedGear
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->andWhere('gearId = :gearId')
            ->setParameter('gearId', $gearId)
            ->andWhere('type = :type')
            ->setParameter('type', GearType::IMPORTED->value);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Gear "%s" not found', $gearId));
        }

        return $this->hydrate($result);
    }
}
