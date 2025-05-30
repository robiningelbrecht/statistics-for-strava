<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\CustomGear;

use App\Domain\Strava\Gear\Gears;
use App\Domain\Strava\Gear\GearType;
use App\Domain\Strava\Gear\ImportedGear\ImportedGear;
use App\Domain\Strava\Gear\ProvideGearRepositoryHelpers;
use App\Infrastructure\Repository\DbalRepository;
use Doctrine\DBAL\Connection;

final readonly class DbalCustomGearRepository extends DbalRepository implements CustomGearRepository
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

    public function removeAll(): void
    {
        $this->connection->executeStatement('DELETE FROM gear WHERE type = :type', [
            'type' => GearType::CUSTOM->value,
        ]);
    }
}
