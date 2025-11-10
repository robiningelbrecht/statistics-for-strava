<?php

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Gear\CustomGear\CustomGear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\Gears;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ProvideGearRepositoryHelpers;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use Doctrine\DBAL\Connection;

final readonly class DbalImportedGearRepository extends DbalRepository implements ImportedGearRepository
{
    use ProvideGearRepositoryHelpers {
        save as protected parentSave;
        findAll as protected parentFindAll;
        findAllUsed as protected parentFindAllUsed;
    }

    public function __construct(
        Connection $connection,
        private ImportedGearConfig $importedGearConfig,
    ) {
        parent::__construct($connection);
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
        $gears = $this->parentFindAll(
            gearType: GearType::IMPORTED
        );

        return $this->enrichGears($gears);
    }

    public function findAllUsed(): Gears
    {
        $gears = $this->parentFindAllUsed(
            gearType: GearType::IMPORTED
        );

        return $this->enrichGears($gears);
    }

    private function enrichGears(Gears $gears): Gears
    {
        $enrichedGears = Gears::empty();
        /** @var ImportedGear $gear */
        foreach ($gears as $gear) {
            $enrichedGears->add($this->importedGearConfig->enrichGearWithCustomData($gear));
        }

        return $enrichedGears;
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
