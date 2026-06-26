<?php

namespace App\Tests\Domain\Gear\Maintenance\History;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Maintenance\History\DbalGearMaintenanceHistoryRepository;
use App\Domain\Gear\Maintenance\History\GearMaintenanceHistories;
use App\Domain\Gear\Maintenance\History\GearMaintenanceHistory;
use App\Domain\Gear\Maintenance\History\GearMaintenanceHistoryRepository;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class DbalGearMaintenanceHistoryRepositoryTest extends ContainerTestCase
{
    private GearMaintenanceHistoryRepository $gearMaintenanceHistoryRepository;

    public function testAddAndFindAll(): void
    {
        $older = GearMaintenanceHistory::create(
            gearId: GearId::fromUnprefixed('b1'),
            maintenanceTaskId: MaintenanceTaskId::fromUnprefixed('chain-lubed'),
            performedOn: SerializableDateTime::fromString('2025-01-01 00:00:00'),
        );
        $newer = GearMaintenanceHistory::create(
            gearId: GearId::fromUnprefixed('b1'),
            maintenanceTaskId: MaintenanceTaskId::fromUnprefixed('chain-lubed'),
            performedOn: SerializableDateTime::fromString('2025-03-01 00:00:00'),
        );

        $this->gearMaintenanceHistoryRepository->add($older);
        $this->gearMaintenanceHistoryRepository->add($newer);

        // findAll orders by performedOn DESC.
        $this->assertEquals(
            GearMaintenanceHistories::fromArray([$newer, $older]),
            $this->gearMaintenanceHistoryRepository->findAll(),
        );
    }

    public function testFindAllWithoutHistory(): void
    {
        $this->assertEquals(
            GearMaintenanceHistories::empty(),
            $this->gearMaintenanceHistoryRepository->findAll(),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->gearMaintenanceHistoryRepository = new DbalGearMaintenanceHistoryRepository(
            $this->getConnection(),
        );
    }
}
