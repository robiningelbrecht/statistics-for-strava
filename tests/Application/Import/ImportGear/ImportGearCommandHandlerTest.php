<?php

namespace App\Tests\Application\Import\ImportGear;

use App\Application\Import\ImportGear\ImportGear;
use App\Application\Import\ImportGear\ImportGearCommandHandler;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Yaml\Yaml;

class ImportGearCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(4);

        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );

        $this->commandBus->dispatch(new ImportGear($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertEmpty(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );
    }

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(10000);

        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );

        $this->commandBus->dispatch(new ImportGear($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );
    }

    public function testHandleWithDuplicateGearIds(): void
    {
        $ImportGearCommandHandler = new ImportGearCommandHandler(
            $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(ImportedGearRepository::class),
            $this->getContainer()->get(CustomGearRepository::class),
            CustomGearConfig::fromArray(Yaml::parse(<<<YML
enabled: true
hashtagPrefix: 'sfs'
customGears:
  - tag: 'b12659743'
    label: 'Custom Gear 1'
    isRetired: false
  - tag: 'gear-2'
    label: 'Custom Gear 2'
    isRetired: true
  - tag: 'gear-3'
    label: 'Custom Gear 3'
    isRetired: false
YML
            )),
            $this->getContainer()->get(StravaDataImportStatus::class),
            PausedClock::on(SerializableDateTime::some()),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(10000);

        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );

        $ImportGearCommandHandler->handle(new ImportGear($output));

        $this->assertMatchesTextSnapshot($output);
        $this->assertEmpty(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
