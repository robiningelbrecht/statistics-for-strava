<?php

namespace App\Tests\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Gear\Maintenance\InvalidGearMaintenanceConfig;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Yaml\Yaml;

class GearMaintenanceConfigTest extends ContainerTestCase
{
    use MatchesSnapshots;

    /**
     * @param array<string, mixed> $config
     */
    private function createConfig(array $config): GearMaintenanceConfig
    {
        return GearMaintenanceConfig::fromArray($config);
    }

    public function testFromArrayWhenEmpty(): void
    {
        $this->assertFalse(
            $this->createConfig([])->isFeatureEnabled(),
        );
    }

    public function testGetAllReferencedGearIds(): void
    {
        $yml = $this->getValidYml();

        $this->assertEquals(
            GearIds::fromArray([
                GearId::fromUnprefixed('bike-one-gear-id'),
                GearId::fromUnprefixed('bike-two-gear-id'),
            ]),
            $this->createConfig($yml)->getAllReferencedGearIds()
        );
    }

    public function testGetAllReferencedImages(): void
    {
        $yml = $this->getValidYml();

        $this->assertEquals(
            [
                'chain.png',
            ],
            $this->createConfig($yml)->getAllReferencedImages()
        );
    }

    public function testNormalizeGearIds(): void
    {
        $config = $this->createConfig($this->getYmlStringThatNeedsNormalization());
        $config->normalizeGearIds(GearIds::fromArray([GearId::fromUnprefixed('b123456')]));

        $this->assertEquals(
            GearId::fromUnprefixed('b123456'),
            $config->getGearComponents()->getAllReferencedGearIds()->getFirst()
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $yml, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidGearMaintenanceConfig($expectedException));
        $this->createConfig($yml);
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        unset($yml['enabled']);
        yield 'missing "enabled" key' => [$yml, '"enabled" property is required'];

        $yml = self::getValidYml();
        unset($yml['components']);
        yield 'missing "components" key' => [$yml, '"components" property is required'];

        $yml = self::getValidYml();
        $yml['components'] = 'string';
        yield '"components" is not an array' => [$yml, '"components" property must be an array'];

        $yml = self::getValidYml();
        $yml['components'] = [];
        yield '"components" is empty' => [$yml, 'You must configure at least one component'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['id']);
        yield 'missing "components[id]" key' => [$yml, '"id" property is required for each component'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['label']);
        yield 'missing "components[label]" key' => [$yml, '"label" property is required for each component'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['attachedTo']);
        yield 'missing "components[attachedTo]" key' => [$yml, '"attachedTo" property is required for each component'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['maintenance']);
        yield 'missing "components[maintenance]" key' => [$yml, '"maintenance" property is required for each component'];

        $yml = self::getValidYml();
        $yml['components'][0]['attachedTo'] = 'string';
        yield '"components[attachedTo]" is not an array' => [$yml, '"attachedTo" property must be an array'];

        $yml = self::getValidYml();
        $yml['components'][0]['purchasePrice'] = [];
        yield 'missing "purchasePrice[amountInCents]" key' => [$yml, '"purchasePrice.amountInCents" property must be a numeric value'];

        $yml = self::getValidYml();
        $yml['components'][0]['purchasePrice'] = ['amountInCents' => 'lol'];
        yield 'invalid "purchasePrice[amountInCents]" key' => [$yml, '"purchasePrice.amountInCents" property must be a numeric value'];

        $yml = self::getValidYml();
        $yml['components'][0]['purchasePrice'] = ['amountInCents' => 3];
        yield 'missing "purchasePrice[currency]" key' => [$yml, '"purchasePrice.currency" property is required'];

        $yml = self::getValidYml();
        $yml['components'][0]['maintenance'] = 'string';
        yield '"components[maintenance]" is not an array' => [$yml, '"maintenance" property must be an array'];

        $yml = self::getValidYml();
        $yml['components'][0]['maintenance'] = [];
        yield '"components[maintenance]" is empty' => [$yml, 'No maintenance tasks configured for component "chain"'];

        $yml = self::getValidYml();
        $yml['components'][0]['imgSrc'] = [];
        yield '"components[imgSrc]" is not an string' => [$yml, '"imgSrc" property must be a string'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['maintenance'][0]['id']);
        yield 'missing "components[maintenance][id]" key' => [$yml, '"id" property is required for each maintenance task'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['maintenance'][0]['label']);
        yield 'missing "components[maintenance][label]" key' => [$yml, '"label" property is required for each maintenance task'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['maintenance'][0]['interval']);
        yield 'missing "components[maintenance][interval]" key' => [$yml, '"interval" property is required for each maintenance task'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['maintenance'][0]['interval']['value']);
        yield 'missing "components[maintenance][interval][value]" key' => [$yml, '"interval" property must have "value" and "unit" properties'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['maintenance'][0]['interval']['unit']);
        yield 'missing "components[maintenance][interval][unit]" key' => [$yml, '"interval" property must have "value" and "unit" properties'];

        $yml = self::getValidYml();
        $yml['components'][0]['maintenance'][0]['interval']['unit'] = 'lol';
        yield 'invalid "components[maintenance][interval][unit]"' => [$yml, 'invalid interval unit "lol"'];

        $yml = self::getValidYml();
        $yml['components'][0]['maintenance'][0]['id'] = 'chain-lubed';
        $yml['components'][0]['maintenance'][1]['id'] = 'chain-lubed';
        yield 'duplicate maintenance task ids' => [$yml, 'duplicate maintenance task ids found for component "Some cool chain:" chain-lubed'];

        $yml = self::getValidYml();
        $yml['components'][0]['id'] = 'chain';
        $yml['components'][1]['id'] = 'chain';
        yield 'duplicate component ids' => [$yml, 'duplicate component ids found: chain'];

        $yml = self::getValidYml();
        $yml['ignoreRetiredGear'] = 'lol';
        yield 'ignoreRetiredGear is invalid' => [$yml, '"ignoreRetiredGear" property must be a boolean'];
    }

    private static function getValidYml(): array
    {
        return Yaml::parse(<<<YML
enabled: true
components:
  - id: 'chain'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'bike-one-gear-id'
      - 'bike-two-gear-id'
    maintenance:
      - id: chain-lubed
        label: Lube
        interval:
          value: 500
          unit: km
      - label: Clean
        id: chain-cleaned
        interval:
          value: 200
          unit: hours
      - label: Replace
        id: chain-replaced
        interval:
          value: 500
          unit: days
  - id: 'chain-two'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'bike-one-gear-id'
      - 'bike-two-gear-id'
    maintenance:
      - id: chain-two-lubed
        label: Lube
        interval:
          value: 500
          unit: km
YML
        );
    }

    private function getYmlStringThatNeedsNormalization(): array
    {
        return Yaml::parse(<<<YML
enabled: true
components:
  - id: 'chain'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - '123456'
    maintenance:
      - id: chain-lubed
        label: Lube
        interval:
          value: 500
          unit: km
      - label: Clean
        id: chain-cleaned
        interval:
          value: 200
          unit: hours
      - label: Replace
        id: chain-replaced
        interval:
          value: 500
          unit: days
  - id: 'chain-two'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - '123456'
    maintenance:
      - id: chain-two-lubed
        label: Lube
        interval:
          value: 500
          unit: km
YML
        );
    }
}
