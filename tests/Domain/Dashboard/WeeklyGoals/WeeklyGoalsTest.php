<?php

namespace App\Tests\Domain\Dashboard\WeeklyGoals;

use App\Domain\Dashboard\WeeklyGoals\InvalidWeeklyGoalsConfiguration;
use App\Domain\Dashboard\WeeklyGoals\WeeklyGoal;
use App\Domain\Dashboard\WeeklyGoals\WeeklyGoals;
use App\Domain\Dashboard\WeeklyGoals\WeeklyGoalType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WeeklyGoalsTest extends TestCase
{
    public function testFromConfig(): void
    {
        [
            'label' => 'Cycling',
            'enabled' => true,
            'type' => 'distance',
            'unit' => 'km',
            'goal' => 200,
            'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
        ],
            [
                'label' => 'Cycling',
                'enabled' => true,
                'type' => 'elevation',
                'unit' => 'm',
                'goal' => 7500,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Cycling',
                'enabled' => true,
                'type' => 'movingTime',
                'unit' => 'hour',
                'goal' => 7500,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],

        $this->assertEquals(
            WeeklyGoals::empty(),
            WeeklyGoals::fromConfig([])
        );

        $this->assertEquals(
            WeeklyGoals::fromArray([
                    WeeklyGoal::create(
                        label: 'Cycling',
                        isEnabled: true,
                        type: WeeklyGoalType::DISTANCE,
                        goal: 200,
                        unit: 'km',
                        sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
                    ),
                    WeeklyGoal::create(
                        label: 'Cycling',
                        isEnabled: true,
                        type: 'elevation',
                        goal: 7500,
                        unit: 'm',
                        sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
                    ),
                    WeeklyGoal::create(
                        label: 'Cycling',
                        isEnabled: true,
                        type: 'movingTime',
                        goal: 7500,
                        unit: 'hour',
                        sportTypesToInclude: ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
                    ),
                ]),
            WeeklyGoals::fromConfig(self::getValidYml())
        );
    }


    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidWeeklyGoalsConfiguration($expectedException));
        WeeklyGoals::fromConfig($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        $yml[0] = 'lol';
        yield 'invalid configuration provided' => [$yml, 'Invalid WeeklyGoals configuration provided'];

        $yml = self::getValidYml();
        unset($yml[0]['label']);
        yield 'missing "label" key' => [$yml, '"label" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['enabled']);
        yield 'missing "enabled" key' => [$yml, '"enabled" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['type']);
        yield 'missing "type" key' => [$yml, '"type" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['unit']);
        yield 'missing "unit" key' => [$yml, '"unit" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['goal']);
        yield 'missing "goal" key' => [$yml, '"goal" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['sportTypesToInclude']);
        yield 'missing "sportTypesToInclude" key' => [$yml, '"sportTypesToInclude" property is required'];

        $yml = self::getValidYml();
        $yml[0]['label'] = '';
        yield 'empty "label"' => [$yml, '"label" property cannot be empty'];

        $yml = self::getValidYml();
        $yml[0]['enabled'] = 'LOL';
        yield 'invalid "enabled"' => [$yml, '"enabled" property must be a boolean'];

        $yml = self::getValidYml();
        $yml[0]['goal'] = 'LOL';
        yield 'invalid "goal"' => [$yml, '"goal" property must be a valid number'];

        $yml = self::getValidYml();
        $yml[0]['type'] = 'LOL';
        yield 'invalid "type"' => [$yml, '"LOL" is not a valid type'];

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
        $yml[0]['type'] = 'distance';
        $yml[0]['unit'] = 'minute';
        yield 'invalid "type and unit" combo' => [$yml, 'The unit "minute" is not valid for goal type "distance"'];

        $yml = self::getValidYml();
        $yml[0]['type'] = 'movingTime';
        $yml[0]['unit'] = 'km';
        yield 'invalid "type and unit" combo 2' => [$yml, 'The unit "km" is not valid for goal type "movingTime"'];
    }

    private static function getValidYml(): array
    {
        return [
            [
                'label' => 'Cycling',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 200,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Cycling',
                'enabled' => true,
                'type' => 'elevation',
                'unit' => 'm',
                'goal' => 7500,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Cycling',
                'enabled' => true,
                'type' => 'movingTime',
                'unit' => 'hour',
                'goal' => 7500,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
        ];
    }
}
