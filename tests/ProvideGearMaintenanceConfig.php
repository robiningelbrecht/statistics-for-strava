<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;

trait ProvideGearMaintenanceConfig
{
    abstract protected static function getContainer(): Container;

    protected function importGearMaintenanceConfig(): void
    {
        /** @var KeyValueStore $keyValueStore */
        $keyValueStore = $this->getContainer()->get(KeyValueStore::class);
        $keyValueStore->save(KeyValue::fromState(
            key: Key::GEAR_MAINTENANCE,
            value: Value::fromString(Json::encode(Yaml::parse(<<<YML
enabled: true
hashtagPrefix: sfs
ignoreRetiredGear: true
components:
  - tag: chain
    label: Some cool chain
    imgSrc: chain.png
    attachedTo:
      - g1233776
      - '10130856'
      - retired
    purchasePrice:
      amountInCents: 123456
      currency: 'EUR'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
      - tag: replaced
        label: Replace
        interval:
          value: 1000
          unit: km
      - tag: cleaned
        label: Clean
        interval:
          value: 1000
          unit: km
  - tag: di-2
    label: DI2 Battery
    imgSrc: battery.png
    attachedTo:
      - g1233776
      - g10130856
    maintenance:
      - tag: charged
        label: Charge
        interval:
          value: 11
          unit: hours
gears:
  - gearId: g10130856
    imgSrc: bike.webp
YML))),
        ));
    }
}
