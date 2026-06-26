<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\History;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalGearMaintenanceHistoryRepository extends DbalRepository implements GearMaintenanceHistoryRepository
{
    public function add(GearMaintenanceHistory $gearMaintenanceHistory): void
    {
        $sql = 'INSERT INTO GearMaintenanceHistory (gearMaintenanceHistoryId, gearId, maintenanceTaskId, performedOn)
        VALUES (:gearMaintenanceHistoryId, :gearId, :maintenanceTaskId, :performedOn)';

        $this->connection->executeStatement($sql, [
            'gearMaintenanceHistoryId' => $gearMaintenanceHistory->getId(),
            'gearId' => $gearMaintenanceHistory->getGearId(),
            'maintenanceTaskId' => $gearMaintenanceHistory->getMaintenanceTaskId(),
            'performedOn' => $gearMaintenanceHistory->getPerformedOn(),
        ]);
    }

    public function findAll(): GearMaintenanceHistories
    {
        $results = $this->connection->executeQuery(
            'SELECT * FROM GearMaintenanceHistory ORDER BY performedOn DESC'
        )->fetchAllAssociative();

        return GearMaintenanceHistories::fromArray(array_map(
            $this->hydrate(...),
            $results
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): GearMaintenanceHistory
    {
        return GearMaintenanceHistory::fromState(
            gearMaintenanceHistoryId: GearMaintenanceHistoryId::fromString($result['gearMaintenanceHistoryId']),
            gearId: GearId::fromString($result['gearId']),
            maintenanceTaskId: MaintenanceTaskId::fromString($result['maintenanceTaskId']),
            performedOn: SerializableDateTime::fromString($result['performedOn']),
        );
    }
}
