<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\ActivityBasedMaintenanceTaskTagRepository;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Tests\ContainerTestCase;

class ActivityBasedMaintenanceTaskTagRepositoryTest extends ContainerTestCase
{
    private MaintenanceTaskTagRepository $maintenanceTaskTagRepository;

    public function testFindAll(): void
    {
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->maintenanceTaskTagRepository = new ActivityBasedMaintenanceTaskTagRepository(
            $this->getContainer()->get(ActivityRepository::class),
            GearMaintenanceConfig::fromYmlString($this->getValidYmlString()),
        );
    }

    private static function getValidYmlString(): string
    {
        return <<<YML
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
YML;
    }
}
