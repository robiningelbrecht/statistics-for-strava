<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\History;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'GearMaintenanceHistory')]
#[ORM\Index(name: 'GearMaintenanceHistory_gearIndex', columns: ['gearId'])]
final readonly class GearMaintenanceHistory
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private GearMaintenanceHistoryId $gearMaintenanceHistoryId,
        #[ORM\Column(type: 'string')]
        private GearId $gearId,
        #[ORM\Column(type: 'string')]
        private MaintenanceTaskId $maintenanceTaskId,
        #[ORM\Column(type: 'datetime_immutable')]
        private SerializableDateTime $performedOn,
    ) {
    }

    public static function create(
        GearId $gearId,
        MaintenanceTaskId $maintenanceTaskId,
        SerializableDateTime $performedOn,
    ): self {
        return new self(
            gearMaintenanceHistoryId: GearMaintenanceHistoryId::random(),
            gearId: $gearId,
            maintenanceTaskId: $maintenanceTaskId,
            performedOn: $performedOn,
        );
    }

    public static function fromState(
        GearMaintenanceHistoryId $gearMaintenanceHistoryId,
        GearId $gearId,
        MaintenanceTaskId $maintenanceTaskId,
        SerializableDateTime $performedOn,
    ): self {
        return new self(
            gearMaintenanceHistoryId: $gearMaintenanceHistoryId,
            gearId: $gearId,
            maintenanceTaskId: $maintenanceTaskId,
            performedOn: $performedOn,
        );
    }

    public function getId(): GearMaintenanceHistoryId
    {
        return $this->gearMaintenanceHistoryId;
    }

    public function getGearId(): GearId
    {
        return $this->gearId;
    }

    public function getMaintenanceTaskId(): MaintenanceTaskId
    {
        return $this->maintenanceTaskId;
    }

    public function getPerformedOn(): SerializableDateTime
    {
        return $this->performedOn;
    }
}
