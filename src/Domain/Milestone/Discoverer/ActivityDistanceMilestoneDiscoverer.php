<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\ActivityRecordContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class ActivityDistanceMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT activityId, startDateTime, sportType, distance
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];
        /** @var array<string, array{raw: float, unit: Kilometer}> $records */
        $records = [];

        foreach ($rows as $row) {
            $distanceInMeter = (float) $row['distance'];
            if ($distanceInMeter <= 0) {
                continue;
            }

            $sportType = SportType::from($row['sportType']);
            $sportKey = $sportType->value;
            $distanceInKm = Meter::from($distanceInMeter)->toKilometer();

            if (!isset($records[$sportKey]) || $distanceInMeter > $records[$sportKey]['raw']) {
                $previousValue = $records[$sportKey]['unit'] ?? null;

                $milestones[] = Milestone::create(
                    achievedOn: SerializableDateTime::fromString($row['startDateTime']),
                    category: MilestoneCategory::ACTIVITY_DISTANCE,
                    sportType: $sportType,
                    activityId: ActivityId::fromUnprefixed($row['activityId']),
                    title: 'Longest distance',
                    context: new ActivityRecordContext(
                        value: $distanceInKm,
                        previousValue: $previousValue,
                    ),
                );

                $records[$sportKey] = [
                    'raw' => $distanceInMeter,
                    'unit' => $distanceInKm,
                ];
            }
        }

        return Milestones::fromArray($milestones);
    }
}
