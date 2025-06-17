<?php

namespace App\Tests\Domain\Strava\Activity\Eddington\Config;

use App\Domain\Strava\Activity\Eddington\Config\EddingtonConfigItem;
use App\Domain\Strava\Activity\Eddington\Config\EddingtonConfiguration;
use App\Domain\Strava\Activity\Eddington\InvalidEddingtonConfiguration;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EddingtonConfigurationTest extends TestCase
{
    public function testFromScalarArray(): void
    {
        $theConfig = EddingtonConfiguration::fromArray([
            EddingtonConfigItem::create(
                label: 'Ride',
                showInNavBar: true,
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                ]),
            ),
            EddingtonConfigItem::create(
                label: 'Run',
                showInNavBar: true,
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                ]),
            ),
            EddingtonConfigItem::create(
                label: 'Walk',
                showInNavBar: false,
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::WALK, SportType::HIKE,
                ]),
            ),
        ]);
        $this->assertEquals(
            $theConfig,
            EddingtonConfiguration::fromScalarArray(self::getValidYml()),
        );

        $this->assertEquals(
            $theConfig,
            EddingtonConfiguration::fromScalarArray([])
        );
    }

    #[DataProvider(methodName: 'provideInvalidEddingtonConfig')]
    public function testFromScalarArrayItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidEddingtonConfiguration($expectedException));
        EddingtonConfiguration::fromScalarArray($config);
    }

    public static function provideInvalidEddingtonConfig(): iterable
    {
        $yml = self::getValidYml();
        $yml[0] = 'lol';
        yield 'invalid eddington configuration provided' => [$yml, 'Invalid Eddington configuration provided'];

        $yml = self::getValidYml();
        unset($yml[0]['label']);
        yield 'missing "label" key' => [$yml, '"label" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['showInNavBar']);
        yield 'missing "showInNavBar" key' => [$yml, '"showInNavBar" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['sportTypesToInclude']);
        yield 'missing "sportTypesToInclude" key' => [$yml, '"sportTypesToInclude" property is required'];

        $yml = self::getValidYml();
        $yml[0]['label'] = '';
        yield 'empty "label"' => [$yml, '"label" property cannot be empty'];

        $yml = self::getValidYml();
        $yml[0]['showInNavBar'] = 'LOL';
        yield 'invalid "showInNavBar"' => [$yml, '"showInNavBar" property must be a boolean'];

        $yml = self::getValidYml();
        $yml[0]['sportTypesToInclude'] = 'LOL';
        yield 'invalid "sportTypesToInclude"' => [$yml, '"sportTypesToInclude" property must be an array'];

        $yml = self::getValidYml();
        $yml[0]['sportTypesToInclude'] = [];
        yield 'empty "sportTypesToInclude"' => [$yml, '"sportTypesToInclude" property cannot be empty'];

        $yml = self::getValidYml();
        $yml[0]['sportTypesToInclude'] = ['test'];
        yield 'invalid sport type in "sportTypesToInclude"' => [$yml, '"test" is not a valid sport type'];

        $yml = self::getValidYml();
        $yml[0]['sportTypesToInclude'] = ['Run', 'Ride'];
        yield 'mixed activity types in "sportTypesToInclude"' => [$yml, 'Eddington "Ride" contains sport types with different activity types'];

        $yml = self::getValidYml();
        $yml[2]['showInNavBar'] = true;
        yield 'too many items in navBar"' => [$yml, 'You can only have two Eddingtons with "showInNavBar" set to true'];
    }

    private static function getValidYml(): array
    {
        return [
            [
                'label' => 'Ride',
                'showInNavBar' => true,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Run',
                'showInNavBar' => true,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Walk',
                'showInNavBar' => false,
                'sportTypesToInclude' => ['Walk', 'Hike'],
            ],
        ];
    }
}
