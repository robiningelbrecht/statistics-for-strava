<?php

namespace App\Tests\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Gear\Maintenance\InvalidGearMaintenanceConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Yaml\Yaml;

class GearMaintenanceConfigTest extends TestCase
{
    use MatchesSnapshots;

    public function testFromArrayWhenEmpty(): void
    {
        $this->assertEquals(
            'The gear maintenance feature is disabled.',
            (string) GearMaintenanceConfig::fromArray([]),
        );
    }

    public function testToString(): void
    {
        $this->assertMatchesTextSnapshot(
            (string) GearMaintenanceConfig::fromArray(self::getValidYml())
        );
    }

    public function testGetAllMaintenanceTags(): void
    {
        $yml = $this->getValidYml();

        $this->assertEquals(
            ['#sfs-chain-lubed', '#sfs-chain-cleaned', '#sfs-chain-replaced', '#sfs-chain-two-lubed'],
            GearMaintenanceConfig::fromArray($yml)->getAllMaintenanceTags()
        );
    }

    public function testGetAllReferencedGearIds(): void
    {
        $yml = $this->getValidYml();

        $this->assertEquals(
            GearIds::fromArray([
                GearId::fromUnprefixed('bike-one-gear-id'),
                GearId::fromUnprefixed('bike-two-gear-id'),
                GearId::fromUnprefixed('g12337767'),
            ]),
            GearMaintenanceConfig::fromArray($yml)->getAllReferencedGearIds()
        );
    }

    public function testGetAllReferencedImages(): void
    {
        $yml = $this->getValidYml();

        $this->assertEquals(
            [
                'chain.png',
                'gear1.png',
            ],
            GearMaintenanceConfig::fromArray($yml)->getAllReferencedImages()
        );
    }

    public function testNormalizeGearIds(): void
    {
        $config = GearMaintenanceConfig::fromArray($this->getYmlStringThatNeedsNormalization());
        $config->normalizeGearIds(GearIds::fromArray([GearId::fromUnprefixed('b123456')]));

        $this->assertMatchesTextSnapshot((string) $config);
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $yml, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidGearMaintenanceConfig($expectedException));
        GearMaintenanceConfig::fromArray($yml);
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        unset($yml['enabled']);
        yield 'missing "enabled" key' => [$yml, '"enabled" property is required'];

        $yml = self::getValidYml();
        unset($yml['hashtagPrefix']);
        yield 'missing "hashtagPrefix" key' => [$yml, '"hashtagPrefix" property is required'];

        $yml = self::getValidYml();
        unset($yml['components']);
        yield 'missing "components" key' => [$yml, '"components" property is required'];

        $yml = self::getValidYml();
        unset($yml['gears']);
        yield 'missing "gears" key' => [$yml, '"gears" property is required'];

        $yml = self::getValidYml();
        $yml['components'] = 'string';
        yield '"components" is not an array' => [$yml, '"components" property must be an array'];

        $yml = self::getValidYml();
        $yml['components'] = [];
        yield '"components" is empty' => [$yml, 'You must configure at least one component'];

        $yml = self::getValidYml();
        $yml['countersResetMode'] = 'lol';
        yield '"countersResetMode" is invalid' => [$yml, 'invalid countersResetMode "lol"'];

        $yml = self::getValidYml();
        unset($yml['components'][0]['tag']);
        yield 'missing "components[tag]" key' => [$yml, '"tag" property is required for each component'];

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
        unset($yml['components'][0]['maintenance'][0]['tag']);
        yield 'missing "components[maintenance][tag]" key' => [$yml, '"tag" property is required for each maintenance task'];

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
        $yml['components'][0]['maintenance'][0]['tag'] = 'lubed';
        $yml['components'][0]['maintenance'][1]['tag'] = 'lubed';
        yield 'duplicate maintenance tags' => [$yml, 'duplicate maintenance tags found for component "Some cool chain:" lubed'];

        $yml = self::getValidYml();
        $yml['components'][0]['tag'] = 'chain';
        $yml['components'][1]['tag'] = 'chain';
        yield 'duplicate component tags' => [$yml, 'duplicate component tags found: chain'];

        $yml = self::getValidYml();
        $yml['gears'] = 'string';
        yield '"gears" is not an array' => [$yml, '"gears" property must be an array'];

        $yml = self::getValidYml();
        $yml['gears'][0]['gearId'] = '';
        yield '"gears[gearId]" is empty' => [$yml, '"gearId" property is required for each gear'];

        $yml = self::getValidYml();
        $yml['gears'][0]['imgSrc'] = '';
        yield '"gears[imgSrc]" is empty' => [$yml, '"imgSrc" property is required for each gear'];

        $yml = self::getValidYml();
        $yml['ignoreRetiredGear'] = 'lol';
        yield 'ignoreRetiredGear is invalid' => [$yml, '"ignoreRetiredGear" property must be a boolean'];
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
      - 'bike-one-gear-id'
      - 'bike-two-gear-id'
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
      - 'bike-one-gear-id'
      - 'bike-two-gear-id'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
gears:
  - gearId: 'g12337767'
    imgSrc: 'gear1.png'
YML
        );
    }

    private function getYmlStringThatNeedsNormalization(): array
    {
        return Yaml::parse(<<<YML
enabled: true
hashtagPrefix: 'sfs'
components:
  - tag: 'chain'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'b123456'
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
      - '123456'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
gears:
  - gearId: '123456'
    imgSrc: 'gear1.png'
YML
        );
    }
}
