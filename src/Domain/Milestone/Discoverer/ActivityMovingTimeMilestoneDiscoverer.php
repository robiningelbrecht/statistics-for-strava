<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityRecordContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class ActivityMovingTimeMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT activityId, startDateTime, sportType, movingTimeInSeconds
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];
        /** @var array<string, array{raw: int, unit: Seconds}> $records */
        $records = [];

        foreach ($rows as $row) {
            $movingTime = (int) $row['movingTimeInSeconds'];
            if ($movingTime <= 0) {
                continue;
            }

            $sportType = SportType::tryFrom($row['sportType']);
            if (null === $sportType) {
                continue;
            }
            $sportKey = $sportType->value;
            $seconds = Seconds::from($movingTime);

            if (!isset($records[$sportKey]) || $movingTime > $records[$sportKey]['raw']) {
                $previousValue = $records[$sportKey]['unit'] ?? null;

                $milestones[] = Milestone::create(
                    achievedOn: SerializableDateTime::fromString($row['startDateTime']),
                    category: MilestoneCategory::ACTIVITY_MOVING_TIME,
                    sportType: $sportType,
                    activityId: ActivityId::fromString($row['activityId']),
                    title: 'Longest activity',
                    context: new ActivityRecordContext(
                        value: $seconds,
                        previousValue: $previousValue,
                    ),
                );

                $records[$sportKey] = [
                    'raw' => $movingTime,
                    'unit' => $seconds,
                ];
            }
        }

        return Milestones::fromArray($milestones);
    }
}
