<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

use App\Domain\Gear\Gears;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ProvideGearRepositoryHelpers;
use App\Infrastructure\Repository\DbalRepository;
use Doctrine\DBAL\Connection;

final readonly class DbalCustomGearRepository extends DbalRepository implements CustomGearRepository
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

    public function save(CustomGear $gear): void
    {
        $this->parentSave(
            gear: $gear,
            gearType: GearType::CUSTOM
        );
    }

    public function findAll(): Gears
    {
        return $this->parentFindAll(
            gearType: GearType::CUSTOM
        );
    }

    public function findAllUsed(): Gears
    {
        return $this->parentFindAllUsed(
            gearType: GearType::CUSTOM
        );
    }

    public function removeAll(): void
    {
        $this->connection->executeStatement('DELETE FROM gear WHERE type = :type', [
            'type' => GearType::CUSTOM->value,
        ]);
    }
}
