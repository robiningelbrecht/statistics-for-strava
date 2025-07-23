<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindActivityLocations;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class FindActivityLocationsQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindActivityLocations);
        /** @var array<int, array{0: float, 1: float, 2: int}> $results */
        $results = $this->connection->executeQuery(
            <<<SQL
                SELECT startingCoordinateLongitude, startingCoordinateLatitude, numberOfActivities FROM
                (
                    SELECT
                           MIN(activityId) as activityId,
                           COALESCE(JSON_EXTRACT(location, '$.city'), JSON_EXTRACT(location, '$.county'), JSON_EXTRACT(location, '$.municipality')) as selectedLocation,
                           COUNT(*) as numberOfActivities
                    FROM Activity
                    WHERE location IS NOT NULL
                    AND strftime('%Y',startDateTime) IN (:years)
                    GROUP BY selectedLocation
                ) tmp
                INNER JOIN Activity ON tmp.activityId = Activity.activityId
                ORDER BY numberOfActivities DESC
            SQL,
            [
                'years' => array_map('strval', $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchAllNumeric();

        return new FindActivityLocationsResponse($results);
    }
}
