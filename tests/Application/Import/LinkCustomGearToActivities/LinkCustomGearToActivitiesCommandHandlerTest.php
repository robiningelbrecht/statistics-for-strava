<?php

namespace App\Tests\Application\Import\LinkCustomGearToActivities;

use App\Application\Import\LinkCustomGearToActivities\LinkCustomGearToActivities;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\CustomGear\CustomGearBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class LinkCustomGearToActivitiesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );

        $this->getContainer()->get(CustomGearRepository::class)->save(
            CustomGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('custom'))
                ->build()
        );

        $this->getContainer()->get(CustomGearRepository::class)->save(
            CustomGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('custom-two'))
                ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('with-strava-gear'))
                    ->withGearId(GearId::fromUnprefixed('b12659861'))
                    ->withName('Activity with Strava gear #sfs-custom')
                    ->build(), []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('without-gear-but-not-tagged'))
                    ->withoutGearId()
                    ->withName('Activity without gear but not tagged')
                    ->build(), []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('with-gear-but-not-tagged'))
                    ->withGearId(GearId::fromUnprefixed('some-gear-id'))
                    ->withName('Activity with gear but not tagged')
                    ->build(), []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('without-gear-and-tagged'))
                    ->withoutGearId()
                    ->withName('Activity without gear and tagged #sfs-custom')
                    ->build(), []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('without-gear-and-tagged-2'))
                    ->withoutGearId()
                    ->withName('Activity without gear and tagged #sfs-custom-two')
                    ->build(), []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('without-gear-and-tagged-3'))
                    ->withoutGearId()
                    ->withName('Activity without gear and tagged #sfs-custom')
                    ->build(), []
            )
        );

        $this->commandBus->dispatch(new LinkCustomGearToActivities($output));
        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM Activity')->fetchAllAssociative()
        );
        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM Gear')->fetchAllAssociative()
        );
    }

    public function testHandleWithMultipleTagsOnActicityTitle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );

        $this->getContainer()->get(CustomGearRepository::class)->save(
            CustomGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('custom'))
                ->build()
        );

        $this->getContainer()->get(CustomGearRepository::class)->save(
            CustomGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('custom-two'))
                ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('without-gear-but-not-tagged'))
                    ->withoutGearId()
                    ->withName('Activity without gear but not tagged')
                    ->build(), []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('without-gear-and-tagged'))
                    ->withoutGearId()
                    ->withName('Activity without gear and tagged #sfs-custom #sfs-custom-two')
                    ->build(), []
            )
        );

        $this->commandBus->dispatch(new LinkCustomGearToActivities($output));
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
