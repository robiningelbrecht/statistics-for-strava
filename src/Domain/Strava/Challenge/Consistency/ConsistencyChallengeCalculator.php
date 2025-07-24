<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\Consistency\FindConsistencyMetricsPerMonth\FindConsistencyMetricsPerMonth;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class ConsistencyChallengeCalculator
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private QueryBus $queryBus,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateFor(Months $months, ConsistencyChallenges $challenges): array
    {
        $months = $months->reverse();

        $consistency = [];
        /** @var ConsistencyChallenge $challenge */
        foreach ($challenges as $challenge) {
            if (!$challenge->isEnabled()) {
                continue;
            }

            if (!$this->activityRepository->hasForSportTypes($challenge->getSportTypesToInclude())) {
                continue;
            }

            $response = $this->queryBus->ask(new FindConsistencyMetricsPerMonth($challenge->getSportTypesToInclude()));

            foreach ($months as $month) {
                if (!$metrics = $response->getConsistencyMetricsForMonth($month)) {
                    $consistency[$challenge->getId()][$month->getId()] = false;
                    continue;
                }

                $challengeGoal = $challenge->getGoal();
                [$numberOfActivities, $totalDistance, $maxDistance, $totalElevation, $maxElevation, $movingTime, $totalCaloriesBurnt] = $metrics;

                $consistency[$challenge->getId()][$month->getId()] = match ($challenge->getType()) {
                    ChallengeConsistencyType::DISTANCE => [
                        'goalHasBeenReached' => $this->checkIfGoalHasBeenReached(
                            $challengeGoal,
                            $challengeGoal->convertKilometerToUnit($totalDistance)
                        ),
                        'actualValue' => $challengeGoal->convertKilometerToUnit($totalDistance),
                    ],
                    ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY => [
                        'goalHasBeenReached' => $this->checkIfGoalHasBeenReached(
                            $challengeGoal,
                            $challengeGoal->convertKilometerToUnit($maxDistance)
                        ),
                        'actualValue' => $challengeGoal->convertKilometerToUnit($maxDistance),
                    ],
                    ChallengeConsistencyType::ELEVATION => [
                        'goalHasBeenReached' => $this->checkIfGoalHasBeenReached(
                            $challengeGoal,
                            $challengeGoal->convertMeterToUnit($totalElevation)
                        ),
                        'actualValue' => $challengeGoal->convertMeterToUnit($totalElevation),
                    ],
                    ChallengeConsistencyType::ELEVATION_IN_ONE_ACTIVITY => [
                        'goalHasBeenReached' => $this->checkIfGoalHasBeenReached(
                            $challengeGoal,
                            $challengeGoal->convertMeterToUnit($maxElevation)
                        ),
                        'actualValue' => $challengeGoal->convertMeterToUnit($maxElevation),
                    ],
                    ChallengeConsistencyType::MOVING_TIME => [
                        'goalHasBeenReached' => $this->checkIfGoalHasBeenReached(
                            $challengeGoal,
                            $challengeGoal->convertSecondsToUnit($movingTime)
                        ),
                        'actualValue' => $challengeGoal->convertSecondsToUnit($movingTime),
                    ],
                    ChallengeConsistencyType::NUMBER_OF_ACTIVITIES => [
                        'goalHasBeenReached' => $numberOfActivities >= $challengeGoal->toInt(),
                        'actualValue' => $numberOfActivities,
                    ],
                    ChallengeConsistencyType::CALORIES => [
                        'goalHasBeenReached' => $totalCaloriesBurnt >= $challengeGoal->toInt(),
                        'actualValue' => $totalCaloriesBurnt,
                    ],
                };
            }
        }

        return $consistency;
    }

    private function checkIfGoalHasBeenReached(ChallengeConsistencyGoal $goal, Unit $actualValue): bool
    {
        return $actualValue->toFloat() >= $goal->toFloat();
    }
}
