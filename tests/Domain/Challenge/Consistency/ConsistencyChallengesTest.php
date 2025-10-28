<?php

namespace App\Tests\Domain\Challenge\Consistency;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Challenge\Consistency\ChallengeConsistencyGoal;
use App\Domain\Challenge\Consistency\ChallengeConsistencyType;
use App\Domain\Challenge\Consistency\ConsistencyChallenge;
use App\Domain\Challenge\Consistency\ConsistencyChallenges;
use App\Domain\Challenge\Consistency\InvalidConsistencyChallengeConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConsistencyChallengesTest extends TestCase
{
    public function testFromConfiguration(): void
    {
        $theConfig = ConsistencyChallenges::fromArray([
            ConsistencyChallenge::create(
                label: 'Ride a total of 200km',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE,
                goal: ChallengeConsistencyGoal::from(
                    200,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Ride a total of 600km',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE,
                goal: ChallengeConsistencyGoal::from(
                    600,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Ride a total of 1250km',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE,
                goal: ChallengeConsistencyGoal::from(
                    1250,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Complete a 100km ride',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY,
                goal: ChallengeConsistencyGoal::from(
                    100,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Climb a total of 7500m',
                isEnabled: true,
                type: ChallengeConsistencyType::ELEVATION,
                goal: ChallengeConsistencyGoal::from(
                    7500,
                    ChallengeConsistencyGoal::METER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Complete a 5km run',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY,
                goal: ChallengeConsistencyGoal::from(
                    5,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Complete a 10km run',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY,
                goal: ChallengeConsistencyGoal::from(
                    10,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Complete a half marathon run',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY,
                goal: ChallengeConsistencyGoal::from(
                    21.1,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Run a total of 100km',
                isEnabled: true,
                type: ChallengeConsistencyType::DISTANCE,
                goal: ChallengeConsistencyGoal::from(
                    100,
                    ChallengeConsistencyGoal::KILOMETER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                ]),
            ),
            ConsistencyChallenge::create(
                label: 'Climb a total of 2000m',
                isEnabled: true,
                type: ChallengeConsistencyType::ELEVATION,
                goal: ChallengeConsistencyGoal::from(
                    2000,
                    ChallengeConsistencyGoal::METER
                ),
                sportTypesToInclude: SportTypes::fromArray([
                    SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                ]),
            ),
        ]);

        $this->assertEquals(
            $theConfig,
            ConsistencyChallenges::fromConfig(self::getValidYml()),
        );

        $this->assertEquals(
            $theConfig,
            ConsistencyChallenges::fromConfig([])
        );
    }

    public function testFromConfigurationWithNumberOfActivities(): void
    {
        $theConfig = ConsistencyChallenges::fromConfig([[
            'label' => 'A test',
            'enabled' => true,
            'type' => 'numberOfActivities',
            'unit' => '',
            'goal' => 100,
            'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
        ]]);

        $this->assertEquals(
            $theConfig,
            ConsistencyChallenges::fromArray([
                ConsistencyChallenge::create(
                    label: 'A test',
                    isEnabled: true,
                    type: ChallengeConsistencyType::NUMBER_OF_ACTIVITIES,
                    goal: ChallengeConsistencyGoal::from(
                        100,
                        ChallengeConsistencyGoal::KILOMETER
                    ),
                    sportTypesToInclude: SportTypes::fromArray([
                        SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN,
                    ]),
                ),
            ])
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidConsistencyChallengeConfiguration($expectedException));
        ConsistencyChallenges::fromConfig($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        $yml[0] = 'lol';
        yield 'invalid configuration provided' => [$yml, 'Invalid Challenge configuration provided'];

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
        yield 'invalid "type and unit" combo' => [$yml, 'The unit "minute" is not valid for challenge type "distance"'];

        $yml = self::getValidYml();
        $yml[0]['type'] = 'movingTime';
        $yml[0]['unit'] = 'km';
        yield 'invalid "type and unit" combo 2' => [$yml, 'The unit "km" is not valid for challenge type "movingTime"'];
    }

    private static function getValidYml(): array
    {
        return [
            [
                'label' => 'Ride a total of 200km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 200,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Ride a total of 600km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 600,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Ride a total of 1250km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 1250,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Complete a 100km ride',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 100,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Climb a total of 7500m',
                'enabled' => true,
                'type' => 'elevation',
                'unit' => 'm',
                'goal' => 7500,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Complete a 5km run',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 5,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Complete a 10km run',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 10,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Complete a half marathon run',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 21.1,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Run a total of 100km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 100,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Climb a total of 2000m',
                'enabled' => true,
                'type' => 'elevation',
                'unit' => 'm',
                'goal' => 2000,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
        ];
    }
}
