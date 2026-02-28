<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeMovingTimeContext;
use App\Domain\Milestone\FunComparison\MovingTimeFunComparison;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class CumulativeMovingTimeMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    private const array THRESHOLDS = [
        24, 48, 100, 168, 250, 500, 750, 1_000,
        1_500, 2_000, 2_500, 3_000, 4_000, 5_000,
        7_500, 10_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT startDateTime, sportType, movingTimeInSeconds
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];
        $cumulativeSeconds = 0;
        $thresholdIndex = 0;
        /** @var ?Milestone $previousMilestone */
        $previousMilestone = null;

        foreach ($rows as $row) {
            $seconds = (int) $row['movingTimeInSeconds'];
            if ($seconds <= 0) {
                continue;
            }

            $cumulativeSeconds += $seconds;
            $cumulativeHours = $cumulativeSeconds / 3600;

            while ($thresholdIndex < count(self::THRESHOLDS) && $cumulativeHours >= self::THRESHOLDS[$thresholdIndex]) {
                $threshold = self::THRESHOLDS[$thresholdIndex];
                $thresholdHour = Hour::from($threshold);
                $totalHour = Hour::from(round($cumulativeHours, 1));
                $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

                $previous = null;
                if ($previousMilestone) {
                    $previousContext = $previousMilestone->getContext();
                    assert($previousContext instanceof CumulativeMovingTimeContext);
                    $previous = PreviousMilestone::create(
                        label: number_format((int) $previousContext->getThreshold()->toFloat()).' h',
                        achievedOn: $previousMilestone->getAchievedOn(),
                    );
                }

                $milestone = Milestone::create(
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::CUMULATIVE_MOVING_TIME,
                    sportType: SportType::tryFrom($row['sportType']),
                    activityId: null,
                    title: number_format($threshold).' hours',
                    context: new CumulativeMovingTimeContext(
                        threshold: $thresholdHour,
                        totalMovingTime: $totalHour,
                    ),
                    previous: $previous,
                    funComparison: MovingTimeFunComparison::resolve($thresholdHour),
                );

                $milestones[] = $milestone;
                $previousMilestone = $milestone;
                ++$thresholdIndex;
            }
        }

        return Milestones::fromArray($milestones);
    }
}
