<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\ActivityBasedMaintenanceTaskTagRepository;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTag;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTags;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\String\Tag;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use Symfony\Component\Yaml\Yaml;

class ActivityBasedMaintenanceTaskTagRepositoryTest extends ContainerTestCase
{
    private MaintenanceTaskTagRepository $maintenanceTaskTagRepository;

    public function testFindAll(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withName(Name::fromString('#sfs-chain-lubed'))
                ->withGearId(GearId::fromUnprefixed('g12337767'))
                ->build(), []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withName(Name::fromString('#sfs-chain-two-lubed'))
                ->withGearId(GearId::fromUnprefixed('g12337767'))
                ->build(), []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withName(Name::fromString('#sfs-chain-random'))
                ->withGearId(GearId::fromUnprefixed('g12337767'))
                ->build(), []
        ));

        $this->assertEquals(
            MaintenanceTaskTags::fromArray([
                MaintenanceTaskTag::for(
                    maintenanceTaskTag: Tag::fromString('#sfs-chain-lubed'),
                    taggedOnActivityId: ActivityId::fromUnprefixed(1),
                    taggedForGearId: GearId::fromUnprefixed('g12337767'),
                    taggedForGearComponentName: Name::fromString('Some cool chain'),
                    taggedOn: SerializableDateTime::fromString('2023-10-10'),
                    activityName: '#sfs-chain-lubed',
                    isValid: true
                ),
                MaintenanceTaskTag::for(
                    maintenanceTaskTag: Tag::fromString('#sfs-chain-two-lubed'),
                    taggedOnActivityId: ActivityId::fromUnprefixed(2),
                    taggedForGearId: GearId::fromUnprefixed('g12337767'),
                    taggedForGearComponentName: Name::fromString('Some cool chain'),
                    taggedOn: SerializableDateTime::fromString('2023-10-10'),
                    activityName: '#sfs-chain-two-lubed',
                    isValid: false
                ),
            ]),
            $this->maintenanceTaskTagRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->maintenanceTaskTagRepository = new ActivityBasedMaintenanceTaskTagRepository(
            $this->getContainer()->get(ActivityRepository::class),
            GearMaintenanceConfig::fromArray($this->getValidYml()),
        );
    }

    private static function getValidYml(): array
    {
        return Yaml::parse(<<<YML
enabled: true
hashtagPrefix: 'sfs'
components:
  - tag: 'chain'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'g12337767'
      - 'g10130856'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
      - label: Clean
        tag: cleaned
        interval:
          value: 200
          unit: hours
      - label: Replace
        tag: replaced
        interval:
          value: 500
          unit: days
  - tag: 'chain-two'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'g10130856'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
gears:
  - gearId: 'g12337767'
    imgSrc: 'gear1.png'
YML);
    }
}
