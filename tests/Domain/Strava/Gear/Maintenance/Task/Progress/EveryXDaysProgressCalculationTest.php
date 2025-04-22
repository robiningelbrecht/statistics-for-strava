<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\Maintenance\Task\IntervalUnit;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\EveryXDaysProgressCalculation;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgress;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\ProgressCalculationContext;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use Symfony\Contracts\Translation\TranslatorInterface;

class EveryXDaysProgressCalculationTest extends ContainerTestCase
{
    private EveryXDaysProgressCalculation $calculation;

    public function testCalculate(): void
    {
        $this->assertEquals(
            MaintenanceTaskProgress::from(
                percentage: 50,
                description: '2 days',
            ),
            $this->calculation->calculate(
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculation = new EveryXDaysProgressCalculation(
            $this->getContainer()->get(TranslatorInterface::class),
            PausedClock::on(SerializableDateTime::fromString('2025-01-01'))
        );
    }
}
