<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalGearStatsRepository extends DbalRepository implements GearStatsRepository
{
    public function findStatsPerGearIdPerDay(): GearStats
    {
        $sql = <<<'SQL'
                SELECT
                    gearId, DATE(startDateTime) AS startDate,
                    SUM(SUM(distance)) OVER (
                        PARTITION BY gearId
                        ORDER BY DATE(startDateTime)
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                        ) AS cumulativeDistance
                FROM Activity
                WHERE gearId IS NOT NULL
                GROUP BY startDate, gearId
                ORDER BY gearId, startDate;
                SQL;

        $gearStats = GearStats::empty();
        if (!$results = $this->connection->executeQuery($sql)->fetchAllAssociative()) {
            return $gearStats;
        }

        foreach ($results as $result) {
            $gearStats->addStat(
                gearId: GearId::fromString($result['gearId']),
                date: SerializableDateTime::fromString($result['startDate']),
                distance: Meter::from($result['cumulativeDistance'])->toKilometer()
            );
        }

        return $gearStats;
    }
}
