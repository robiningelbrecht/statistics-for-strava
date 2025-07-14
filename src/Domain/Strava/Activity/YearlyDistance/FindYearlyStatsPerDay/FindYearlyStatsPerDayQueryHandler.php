<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance\FindYearlyStatsPerDay;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class FindYearlyStatsPerDayQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindYearlyStatsPerDay);

        $sql = <<<'SQL'
                SELECT
                    strftime('%Y', startDateTime) AS year, DATE(startDateTime) AS startDate,
                    activityType,
                    SUM(SUM(distance)) OVER (
                        PARTITION BY strftime('%Y', startDateTime), activityType
                        ORDER BY DATE(startDateTime)
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                        ) AS cumulativeDistance
                FROM Activity
                GROUP BY startDate, activityType
                ORDER BY startDate DESC;
                SQL;

        $response = FindYearlyStatsPerDayResponse::empty();
        if (!$results = $this->connection->executeQuery($sql)->fetchAllAssociative()) {
            return $response;
        }

        foreach ($results as $result) {
            $response->add(
                date: SerializableDateTime::fromString($result['startDate']),
                activityType: ActivityType::from($result['activityType']),
                distance: Meter::from($result['cumulativeDistance'])->toKilometer()
            );
        }

        return $response;
    }
}
