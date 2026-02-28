<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityRecordContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class ActivityElevationMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT activityId, startDateTime, sportType, elevation
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];
        /** @var array<string, array{raw: float, unit: Meter}> $records */
        $records = [];

        foreach ($rows as $row) {
            $elevationInMeter = (float) $row['elevation'];
            if ($elevationInMeter <= 0) {
                continue;
            }

            $sportType = SportType::tryFrom($row['sportType']);
            if (null === $sportType) {
                continue;
            }
            $sportKey = $sportType->value;
            $elevation = Meter::from($elevationInMeter);

            if (!isset($records[$sportKey]) || $elevationInMeter > $records[$sportKey]['raw']) {
                $previousValue = $records[$sportKey]['unit'] ?? null;

                $milestones[] = Milestone::create(
                    achievedOn: SerializableDateTime::fromString($row['startDateTime']),
                    category: MilestoneCategory::ACTIVITY_ELEVATION,
                    sportType: $sportType,
                    activityId: ActivityId::fromUnprefixed($row['activityId']),
                    title: 'Most elevation',
                    context: new ActivityRecordContext(
                        value: $elevation,
                        previousValue: $previousValue,
                    ),
                );

                $records[$sportKey] = [
                    'raw' => $elevationInMeter,
                    'unit' => $elevation,
                ];
            }
        }

        return Milestones::fromArray($milestones);
    }
}
