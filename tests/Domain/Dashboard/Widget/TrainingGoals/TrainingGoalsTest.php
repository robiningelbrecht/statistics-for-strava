<?php

namespace App\Tests\Domain\Dashboard\Widget\TrainingGoals;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Dashboard\Widget\TrainingGoals\InvalidTrainingGoalsConfiguration;
use App\Domain\Dashboard\Widget\TrainingGoals\TrainingGoal;
use App\Domain\Dashboard\Widget\TrainingGoals\TrainingGoalPeriod;
use App\Domain\Dashboard\Widget\TrainingGoals\TrainingGoals;
use App\Domain\Dashboard\Widget\TrainingGoals\TrainingGoalType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrainingGoalsTest extends TestCase
{
    public function testFromConfig(): void
    {
        $this->assertEquals(
            TrainingGoals::empty(),
            TrainingGoals::fromConfig([])
        );

        $this->assertEquals(
            TrainingGoals::fromArray([
                TrainingGoal::create(
                    label: 'Cycling',
                    isEnabled: true,
                    type: TrainingGoalType::DISTANCE,
                    period: TrainingGoalPeriod::WEEKLY,
                    goal: 200,
                    unit: 'km',
                    sportTypesToInclude: SportTypes::fromArray([SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE]),
                ),
                TrainingGoal::create(
                    label: 'Cycling',
                    isEnabled: true,
                    type: TrainingGoalType::ELEVATION,
                    period: TrainingGoalPeriod::WEEKLY,
                    goal: 7500,
                    unit: 'm',
                    sportTypesToInclude: SportTypes::fromArray([SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE]),
                ),
                TrainingGoal::create(
                    label: 'Cycling',
                    isEnabled: true,
                    type: TrainingGoalType::MOVING_TIME,
                    period: TrainingGoalPeriod::WEEKLY,
                    goal: 7500,
                    unit: 'hour',
                    sportTypesToInclude: SportTypes::fromArray([SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE]),
                ),
            ]),
            TrainingGoals::fromConfig(self::getValidYml())
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidTrainingGoalsConfiguration($expectedException));
        TrainingGoals::fromConfig($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        $yml['lol'][0] = 'lol';
        yield 'invalid period provided' => [$yml, '"lol" is not a valid goal period'];

        $yml = self::getValidYml();
        $yml['weekly'][0] = 'lol';
        yield 'invalid configuration provided' => [$yml, 'Invalid TrainingGoals configuration provided'];

        $yml = self::getValidYml();
        unset($yml['weekly'][0]['label']);
        yield 'missing "label" key' => [$yml, '"label" property is required'];

        $yml = self::getValidYml();
        unset($yml['weekly'][0]['enabled']);
        yield 'missing "enabled" key' => [$yml, '"enabled" property is required'];

        $yml = self::getValidYml();
        unset($yml['weekly'][0]['type']);
        yield 'missing "type" key' => [$yml, '"type" property is required'];

        $yml = self::getValidYml();
        unset($yml['weekly'][0]['unit']);
        yield 'missing "unit" key' => [$yml, '"unit" property is required'];

        $yml = self::getValidYml();
        unset($yml['weekly'][0]['goal']);
        yield 'missing "goal" key' => [$yml, '"goal" property is required'];

        $yml = self::getValidYml();
        unset($yml['weekly'][0]['sportTypesToInclude']);
        yield 'missing "sportTypesToInclude" key' => [$yml, '"sportTypesToInclude" property is required'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['label'] = '';
        yield 'empty "label"' => [$yml, '"label" property cannot be empty'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['enabled'] = 'LOL';
        yield 'invalid "enabled"' => [$yml, '"enabled" property must be a boolean'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['goal'] = 'LOL';
        yield 'invalid "goal"' => [$yml, '"goal" property must be a valid number'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['type'] = 'LOL';
        yield 'invalid "type"' => [$yml, '"LOL" is not a valid goalType'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['sportTypesToInclude'] = 'LOL';
        yield 'invalid "sportTypesToInclude"' => [$yml, '"sportTypesToInclude" property must be an array'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['sportTypesToInclude'] = [];
        yield 'empty "sportTypesToInclude"' => [$yml, '"sportTypesToInclude" property cannot be empty'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['sportTypesToInclude'] = ['test'];
        yield 'invalid sport type in "sportTypesToInclude"' => [$yml, '"test" is not a valid sport type'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['type'] = 'distance';
        $yml['weekly'][0]['unit'] = 'minute';
        yield 'invalid "type and unit" combo' => [$yml, 'The unit "minute" is not valid for goal type "distance"'];

        $yml = self::getValidYml();
        $yml['weekly'][0]['type'] = 'movingTime';
        $yml['weekly'][0]['unit'] = 'km';
        yield 'invalid "type and unit" combo 2' => [$yml, 'The unit "km" is not valid for goal type "movingTime"'];
    }

    private static function getValidYml(): array
    {
        return [
            'weekly' => [
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
            ],
        ];
    }
}
