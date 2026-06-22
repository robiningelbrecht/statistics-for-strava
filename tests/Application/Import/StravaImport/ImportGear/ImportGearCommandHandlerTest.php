<?php

namespace App\Tests\Application\Import\StravaImport\ImportGear;

use App\Application\Import\StravaImport\ImportGear\ImportGear;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\GearBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportGearCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(3);

        $this->getContainer()->get(GearRepository::class)->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build(),
            ['gear_id' => 'b12659861']
        ));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId(GearId::fromUnprefixed('b12659743'))
                ->build(),
            ['gear_id' => 'b12659743']
        ));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed('b12659792'))
                ->build(),
            ['gear_id' => 'b12659792']
        ));

        $this->commandBus->dispatch(new ImportGear($output, null));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleWithUnexpectedError(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);
        $this->strava->triggerExceptionOnNextCall();

        $this->getContainer()->get(GearRepository::class)->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build(),
            ['gear_id' => 'b12659861']
        ));

        $this->commandBus->dispatch(new ImportGear($output, null));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(10000);

        $this->getContainer()->get(GearRepository::class)->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build(),
            ['gear_id' => 'b12659861']
        ));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId(GearId::fromUnprefixed('b12659743'))
                ->build(),
            ['gear_id' => 'b12659743']
        ));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed('b12659792'))
                ->build(),
            ['gear_id' => 'b12659792']
        ));

        $this->commandBus->dispatch(new ImportGear($output, null));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandlePartialImport(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(10000);

        $this->getContainer()->get(GearRepository::class)->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build(),
            ['gear_id' => 'b12659861']
        ));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId(GearId::fromUnprefixed('b12659743'))
                ->build(),
            ['gear_id' => 'b12659743']
        ));
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed('b12659792'))
                ->build(),
            ['gear_id' => 'b12659792']
        ));

        $this->commandBus->dispatch(new ImportGear($output, ActivityIds::fromArray([ActivityId::fromUnprefixed('1')])));

        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
