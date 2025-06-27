<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Calendar\Months;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class ConsistencyChallengeCalculator
{
    public function __construct(
        private ActivityRepository $activityRepository,
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
            $activities = $this->activityRepository->findBySportTypes($challenge->getSportTypesToInclude());
            if ($activities->isEmpty()) {
                continue;
            }

            foreach ($months as $month) {
                $activitiesInCurrentMonth = $activities->filterOnMonth($month);
                if ($activitiesInCurrentMonth->isEmpty()) {
                    $consistency[$challenge->getId()][$month->getId()] = 0;
                    continue;
                }

                $challengeGoal = $challenge->getGoal();

                $challengeCompleted = match ($challenge->getType()) {
                    ChallengeConsistencyType::DISTANCE => $this->checkIfGoalHasBeenReached(
                        $challengeGoal,
                        $challengeGoal->convertKilometerToUnit(
                            Kilometer::from(
                                $activitiesInCurrentMonth->sum(fn (Activity $a) => $a->getDistance()->toFloat())
                            )
                        )
                    ),
                    ChallengeConsistencyType::DISTANCE_IN_ONE_ACTIVITY => $this->checkIfGoalHasBeenReached(
                        $challengeGoal,
                        $challengeGoal->convertKilometerToUnit(
                            Kilometer::from(
                                $activitiesInCurrentMonth->max(fn (Activity $a) => $a->getDistance()->toFloat())
                            )
                        )
                    ),
                    ChallengeConsistencyType::ELEVATION => $this->checkIfGoalHasBeenReached(
                        $challengeGoal,
                        $challengeGoal->convertMeterToUnit(
                            Meter::from(
                                $activitiesInCurrentMonth->sum(fn (Activity $a) => $a->getElevation()->toFloat())
                            )
                        )
                    ),
                    ChallengeConsistencyType::ELEVATION_IN_ONE_ACTIVITY => $this->checkIfGoalHasBeenReached(
                        $challengeGoal,
                        $challengeGoal->convertMeterToUnit(
                            Meter::from(
                                $activitiesInCurrentMonth->max(fn (Activity $a) => $a->getElevation()->toFloat())
                            )
                        )
                    ),
                    ChallengeConsistencyType::MOVING_TIME => $this->checkIfGoalHasBeenReached(
                        $challengeGoal,
                        $challengeGoal->convertSecondsToUnit(
                            Seconds::from(
                                $activitiesInCurrentMonth->sum(fn (Activity $a) => $a->getMovingTimeInSeconds())
                            )
                        )
                    ),
                    ChallengeConsistencyType::NUMBER_OF_ACTIVITIES => count($activitiesInCurrentMonth) >= $challengeGoal->toInt(),
                };

                $consistency[$challenge->getId()][$month->getId()] = $challengeCompleted;
            }
        }

        // Filter out challenges that have never been completed.
        foreach ($consistency as $challengeId => $achievements) {
            if (!empty(array_filter($achievements))) {
                continue;
            }
            unset($consistency[$challengeId]);
        }

        return $consistency;
    }

    private function checkIfGoalHasBeenReached(ChallengeConsistencyGoal $goal, Unit $actualValue): bool
    {
        return $actualValue->toFloat() >= $goal->toFloat();
    }
}
