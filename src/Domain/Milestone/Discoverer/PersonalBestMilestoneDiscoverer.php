<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\PersonalBestContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Infrastructure\ValueObject\Measurement\Length\ConvertableToMeter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
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
            'SELECT be.activityId, be.sportType, be.distanceInMeter, be.timeInSeconds, a.startDateTime
             FROM ActivityBestEffort be
             INNER JOIN Activity a ON a.activityId = be.activityId
             ORDER BY a.startDateTime ASC, be.distanceInMeter ASC'
        )->fetchAllAssociative();

        $distanceMap = $this->buildDistanceMap();

        $milestones = [];
        /** @var array<string, array{time: int, seconds: Seconds}> $records */
        $records = [];

        foreach ($rows as $row) {
            $sportType = SportType::tryFrom($row['sportType']);
            if (null === $sportType) {
                continue;
            }
            $distanceInMeter = (int) $row['distanceInMeter'];
            $timeInSeconds = (int) $row['timeInSeconds'];

            $distance = $distanceMap[$distanceInMeter] ?? null;
            if (null === $distance) {
                continue;
            }

            $recordKey = $sportType->value.'_'.$distanceInMeter;
            $seconds = Seconds::from($timeInSeconds);

            if (!isset($records[$recordKey]) || $timeInSeconds < $records[$recordKey]['time']) {
                $previousTime = $records[$recordKey]['seconds'] ?? null;

                $distanceLabel = ($distance->isLowerThanOne()
                    ? round($distance->toFloat(), 1)
                    : $distance->toInt()).$distance->getSymbol();

                $milestones[] = Milestone::create(
                    achievedOn: SerializableDateTime::fromString($row['startDateTime']),
                    category: MilestoneCategory::PERSONAL_BEST,
                    sportType: $sportType,
                    activityId: ActivityId::fromUnprefixed($row['activityId']),
                    title: $distanceLabel,
                    context: new PersonalBestContext(
                        distance: $distance,
                        time: $seconds,
                        previousTime: $previousTime,
                    ),
                );

                $records[$recordKey] = [
                    'time' => $timeInSeconds,
                    'seconds' => $seconds,
                ];
            }
        }

        return Milestones::fromArray($milestones);
    }

    /**
     * @return array<int, ConvertableToMeter>
     */
    private function buildDistanceMap(): array
    {
        $map = [];

        foreach (ActivityType::cases() as $activityType) {
            foreach ($activityType->getDistancesForBestEffortCalculation() as $distance) {
                $meter = $distance->toMeter()->toInt();
                if (!isset($map[$meter])) {
                    $map[$meter] = $distance;
                }
            }
        }

        return $map;
    }
}
