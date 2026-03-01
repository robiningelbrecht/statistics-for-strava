<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityCountContext;
use App\Domain\Milestone\FunComparison\ActivityCountFunComparison;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneId;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class ActivityCountMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    private const array THRESHOLDS = [
        10, 25, 50, 100, 250, 500, 750, 1_000,
        1_500, 2_000, 2_500, 3_000, 4_000, 5_000,
        7_500, 10_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT startDateTime, sportType
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];
        $count = 0;
        $thresholdIndex = 0;
        /** @var ?Milestone $previousMilestone */
        $previousMilestone = null;

        foreach ($rows as $row) {
            ++$count;

            while ($thresholdIndex < count(self::THRESHOLDS) && $count >= self::THRESHOLDS[$thresholdIndex]) {
                $threshold = self::THRESHOLDS[$thresholdIndex];
                $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

                $previous = null;
                if ($previousMilestone) {
                    $previousContext = $previousMilestone->getContext();
                    assert($previousContext instanceof ActivityCountContext);
                    $previous = PreviousMilestone::create(
                        milestoneId: $previousMilestone->getId(),
                        label: number_format($previousContext->getThreshold()),
                        achievedOn: $previousMilestone->getAchievedOn(),
                    );
                }

                $milestone = Milestone::create(
                    id: MilestoneId::fromParts('activityCount', (string) $threshold),
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::ACTIVITY_COUNT,
                    sportType: SportType::tryFrom($row['sportType']),
                    activityId: null,
                    title: number_format($threshold).' activities',
                    context: new ActivityCountContext(
                        threshold: $threshold,
                        totalCount: $count,
                    ),
                    previous: $previous,
                    funComparison: ActivityCountFunComparison::resolve($threshold),
                );

                $milestones[] = $milestone;
                $previousMilestone = $milestone;
                ++$thresholdIndex;
            }
        }

        return Milestones::fromArray($milestones);
    }
}
