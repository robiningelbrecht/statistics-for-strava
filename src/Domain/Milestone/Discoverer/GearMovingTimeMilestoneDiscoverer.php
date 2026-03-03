<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Milestone\Context\GearMovingTimeContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneIdFactory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class GearMovingTimeMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
        private MilestoneIdFactory $milestoneIdFactory,
    ) {
    }

    private const array THRESHOLDS = [
        24, 48, 100, 168, 250, 500, 750, 1_000,
        1_500, 2_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT a.startDateTime, a.gearId, a.movingTimeInSeconds, g.name as gearName
             FROM Activity a
             INNER JOIN Gear g ON a.gearId = g.gearId
             WHERE a.gearId IS NOT NULL
             ORDER BY a.startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];

        /** @var array<string, array{seconds: int, name: string, idx: int, prev: ?Milestone}> $gearState */
        $gearState = [];

        foreach ($rows as $row) {
            $seconds = (int) $row['movingTimeInSeconds'];
            if ($seconds <= 0) {
                continue;
            }

            $gearId = $row['gearId'];
            $gearName = $row['gearName'];
            $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

            if (!isset($gearState[$gearId])) {
                $gearState[$gearId] = [
                    'seconds' => 0,
                    'name' => $gearName,
                    'idx' => 0,
                    'prev' => null,
                ];
            }

            $state = &$gearState[$gearId];
            $state['seconds'] += $seconds;
            $cumulativeHours = $state['seconds'] / 3600;

            while ($state['idx'] < count(self::THRESHOLDS) && $cumulativeHours >= self::THRESHOLDS[$state['idx']]) {
                $threshold = self::THRESHOLDS[$state['idx']];

                $milestone = Milestone::create(
                    id: $this->milestoneIdFactory->create(),
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::GEAR_MOVING_TIME,
                    sportType: null,
                    activityId: null,
                    context: new GearMovingTimeContext(
                        gearName: $gearName,
                        threshold: Hour::from($threshold),
                    ),
                    previous: $this->buildPreviousMilestone($state['prev']),
                );

                $milestones[] = $milestone;
                $state['prev'] = $milestone;
                ++$state['idx'];
            }
        }

        return Milestones::fromArray($milestones);
    }

    private function buildPreviousMilestone(?Milestone $previous): ?PreviousMilestone
    {
        if (!$previous instanceof Milestone) {
            return null;
        }

        $context = $previous->getContext();
        assert($context instanceof GearMovingTimeContext);

        return PreviousMilestone::create(
            milestoneId: $previous->getId(),
            label: number_format((int) $context->getThreshold()->toFloat()).' h',
            achievedOn: $previous->getAchievedOn(),
        );
    }
}
