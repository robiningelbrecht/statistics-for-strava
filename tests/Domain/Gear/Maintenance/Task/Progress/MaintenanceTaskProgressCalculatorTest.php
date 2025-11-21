<?php

namespace App\Tests\Domain\Gear\Maintenance\Task\Progress;

use App\Domain\Activity\ActivityId;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgress;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Domain\Gear\Maintenance\Task\Progress\ProgressCalculationContext;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class MaintenanceTaskProgressCalculatorTest extends ContainerTestCase
{
    public function testCalculateProgress(): void
    {
        $this->assertEquals(
            MaintenanceTaskProgress::from(100, 'test'),
            new MaintenanceTaskProgressCalculator([
                new ProgressCalculationOne(),
                new ProgressCalculationTwo(),
            ],
                $this->getContainer()->get(GearMaintenanceConfig::class),
                $this->getContainer()->get(MaintenanceTaskTagRepository::class),
                $this->getContainer()->get(GearRepository::class),
            )->calculateProgressFor(
                ProgressCalculationContext::from(
                    gearIds: GearIds::fromArray([GearId::fromUnprefixed('test')]),
                    lastTaggedOnActivityId: ActivityId::fromUnprefixed('test'),
                    lastTaggedOn: SerializableDateTime::fromString('2025-01-03'),
                    intervalUnit: IntervalUnit::EVERY_X_DAYS,
                    intervalValue: 4,
                )
            )
        );
    }

    public function testCalculateProgressForItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('No progress calculation found for interval unit: days'));

        new MaintenanceTaskProgressCalculator(
            [],
            $this->getContainer()->get(GearMaintenanceConfig::class),
            $this->getContainer()->get(MaintenanceTaskTagRepository::class),
            $this->getContainer()->get(GearRepository::class),
        )->calculateProgressFor(
            ProgressCalculationContext::from(
                gearIds: GearIds::fromArray([GearId::fromUnprefixed('test')]),
                lastTaggedOnActivityId: ActivityId::fromUnprefixed('test'),
                lastTaggedOn: SerializableDateTime::fromString('2025-01-03'),
                intervalUnit: IntervalUnit::EVERY_X_DAYS,
                intervalValue: 4,
            )
        );
    }
}
