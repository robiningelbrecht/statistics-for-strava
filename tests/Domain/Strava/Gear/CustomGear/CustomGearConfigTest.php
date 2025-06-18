<?php

namespace App\Tests\Domain\Strava\Gear\CustomGear;

use App\Domain\Strava\Gear\CustomGear\CustomGearConfig;
use App\Domain\Strava\Gear\CustomGear\InvalidCustomGearConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Yaml\Yaml;

class CustomGearConfigTest extends TestCase
{
    use MatchesSnapshots;

    public function testFromArrayWhenEmpty(): void
    {
        $this->assertFalse(CustomGearConfig::fromArray([])->isFeatureEnabled());
    }

    public function testFromArray(): void
    {
        $this->assertTrue(
            CustomGearConfig::fromArray($this->getValidYml())->isFeatureEnabled()
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $yml, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidCustomGearConfig($expectedException));
        CustomGearConfig::fromArray($yml);
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
        unset($yml['customGears']);
        yield 'missing "customGears" key' => [$yml, '"customGears" property is required'];

        $yml = self::getValidYml();
        $yml['customGears'] = 'string';
        yield 'invalid "customGears" key' => [$yml, '"customGears" property must be an array'];

        $yml = self::getValidYml();
        unset($yml['customGears'][0]['tag']);
        yield 'missing "customGears[tag]" key' => [$yml, '"tag" property is required for each custom gear'];

        $yml = self::getValidYml();
        unset($yml['customGears'][0]['label']);
        yield 'missing "customGears[label]" key' => [$yml, '"label" property is required for each custom gear'];

        $yml = self::getValidYml();
        unset($yml['customGears'][0]['isRetired']);
        yield 'missing "customGears[isRetired]" key' => [$yml, '"isRetired" property is required for each custom gear'];

        $yml = self::getValidYml();
        $yml['customGears'][0]['isRetired'] = 'lol';
        yield 'invalid "customGears[isRetired]" key' => [$yml, '"isRetired" property must be a boolean'];

        $yml = self::getValidYml();
        $yml['customGears'][0]['tag'] = 'gearr';
        $yml['customGears'][1]['tag'] = 'gearr';
        yield 'duplicate customGear tags' => [$yml, 'duplicate custom gear tags found: gearr'];
    }

    private static function getValidYml(): array
    {
        return Yaml::parse(<<<YML
enabled: true
hashtagPrefix: 'sfs'
customGears:
  - tag: 'gear-1'
    label: 'Custom Gear 1'
    isRetired: false
  - tag: 'gear-2'
    label: 'Custom Gear 2'
    isRetired: true
  - tag: 'gear-3'
    label: 'Custom Gear 3'
    isRetired: false
YML
        );
    }
}
