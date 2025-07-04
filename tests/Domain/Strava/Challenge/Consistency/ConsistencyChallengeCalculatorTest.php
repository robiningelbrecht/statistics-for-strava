<?php

namespace App\Tests\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\Consistency\ChallengeConsistencyGoal;
use App\Domain\Strava\Challenge\Consistency\ChallengeConsistencyType;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallenge;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallengeCalculator;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallenges;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use Spatie\Snapshots\MatchesSnapshots;

class ConsistencyChallengeCalculatorTest extends ContainerTestCase
{
    use ProvideTestData;
    use MatchesSnapshots;

    private ConsistencyChallengeCalculator $calculator;

    public function testCalculateFor(): void
    {
        $this->provideFullTestSet();

        $this->assertMatchesJsonSnapshot(Json::encode(
            $this->calculator->calculateFor(
                months: Months::create(
                    startDate: SerializableDateTime::fromString('2023-01-01'),
                    now: SerializableDateTime::fromString('2023-12-31'),
                ),
                challenges: ConsistencyChallenges::fromArray([
                    ConsistencyChallenge::create(
                        label: 'Ride a total of 1km',
                        isEnabled: true,
                        type: ChallengeConsistencyType::DISTANCE,
                        goal: ChallengeConsistencyGoal::from(
                            1,
                            ChallengeConsistencyGoal::KILOMETER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                        ]),
                    ),
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
                        label: 'a 2km ride',
                        isEnabled: true,
                        type: ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY,
                        goal: ChallengeConsistencyGoal::from(
                            2,
                            ChallengeConsistencyGoal::KILOMETER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                        ]),
                    ),
                    ConsistencyChallenge::create(
                        label: 'Total elevation',
                        isEnabled: true,
                        type: ChallengeConsistencyType::ELEVATION,
                        goal: ChallengeConsistencyGoal::from(
                            2,
                            ChallengeConsistencyGoal::METER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                        ]),
                    ),
                    ConsistencyChallenge::create(
                        label: 'a 1m elevation ride',
                        isEnabled: true,
                        type: ChallengeConsistencyType::ELEVATION_IN_ONE_ACTIVITY,
                        goal: ChallengeConsistencyGoal::from(
                            2,
                            ChallengeConsistencyGoal::METER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                        ]),
                    ),
                    ConsistencyChallenge::create(
                        label: 'Swim a total of 200km',
                        isEnabled: true,
                        type: ChallengeConsistencyType::DISTANCE,
                        goal: ChallengeConsistencyGoal::from(
                            200,
                            ChallengeConsistencyGoal::KILOMETER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::SWIM,
                        ]),
                    ),
                    ConsistencyChallenge::create(
                        label: 'Quantity',
                        isEnabled: true,
                        type: ChallengeConsistencyType::NUMBER_OF_ACTIVITIES,
                        goal: ChallengeConsistencyGoal::from(
                            2,
                            ChallengeConsistencyGoal::METER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE, SportType::GRAVEL_RIDE, SportType::VIRTUAL_RIDE,
                        ]),
                    ),
                    ConsistencyChallenge::create(
                        label: 'Swim a total of 100km',
                        isEnabled: false,
                        type: ChallengeConsistencyType::DISTANCE,
                        goal: ChallengeConsistencyGoal::from(
                            100,
                            ChallengeConsistencyGoal::KILOMETER
                        ),
                        sportTypesToInclude: SportTypes::fromArray([
                            SportType::SWIM,
                        ]),
                    ),
                ]),
            )
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ConsistencyChallengeCalculator(
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(QueryBus::class),
        );
    }
}
