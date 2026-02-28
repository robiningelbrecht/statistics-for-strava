<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\PersonalBestContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class PersonalBestMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT activityId, startDateTime, sportType, distance, elevation, movingTimeInSeconds, averageSpeed
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $milestones = [];
        /** @var array<string, array{value: float, formatted: string}> $records */
        $records = [];

        foreach ($rows as $row) {
            $sportType = SportType::tryFrom($row['sportType']);
            if (null === $sportType) {
                continue;
            }

            $activityId = ActivityId::fromUnprefixed($row['activityId']);
            $achievedOn = SerializableDateTime::fromString($row['startDateTime']);
            $sportKey = $sportType->value;

            $metrics = $this->extractMetrics($row);

            foreach ($metrics as $metric => $data) {
                $recordKey = $sportKey.'_'.$metric;

                if (!isset($records[$recordKey]) || $data['value'] > $records[$recordKey]['value']) {
                    $previousValue = $records[$recordKey]['formatted'] ?? null;

                    $milestones[] = Milestone::create(
                        achievedOn: $achievedOn,
                        category: MilestoneCategory::PERSONAL_BEST,
                        sportType: $sportType,
                        activityId: $activityId,
                        title: $metric,
                        context: new PersonalBestContext(
                            metric: $metric,
                            value: $data['formatted'],
                            previousValue: $previousValue,
                        ),
                    );

                    $records[$recordKey] = $data;
                }
            }
        }

        return Milestones::fromArray($milestones);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, array{value: float, formatted: string}>
     */
    private function extractMetrics(array $row): array
    {
        $metrics = [];

        $distance = (float) $row['distance'];
        if ($distance > 0) {
            $metrics['Longest distance'] = [
                'value' => $distance,
                'formatted' => round($distance, 1).' km',
            ];
        }

        $elevation = (float) $row['elevation'];
        if ($elevation > 0) {
            $metrics['Most elevation'] = [
                'value' => $elevation,
                'formatted' => (int) $elevation.' m',
            ];
        }

        $movingTime = (int) $row['movingTimeInSeconds'];
        if ($movingTime > 0) {
            $hours = floor($movingTime / 3600);
            $minutes = floor(($movingTime % 3600) / 60);
            $metrics['Longest activity'] = [
                'value' => (float) $movingTime,
                'formatted' => sprintf('%dh %dm', $hours, $minutes),
            ];
        }

        $averageSpeed = (float) $row['averageSpeed'];
        if ($averageSpeed > 0) {
            $metrics['Fastest average speed'] = [
                'value' => $averageSpeed,
                'formatted' => round($averageSpeed, 1).' km/h',
            ];
        }

        return $metrics;
    }
}
