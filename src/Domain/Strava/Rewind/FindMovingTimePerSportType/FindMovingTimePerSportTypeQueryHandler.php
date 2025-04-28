<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindMovingTimePerSportType;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\Connection;

final readonly class FindMovingTimePerSportTypeQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindMovingTimePerSportType);

        $totalMovingTime = (int) $this->connection->executeQuery(
            <<<SQL
                SELECT SUM(movingTimeInSeconds) as movingTimeInSeconds
                FROM Activity
                WHERE strftime('%Y',startDateTime) = :year
            SQL,
            [
                'year' => (string) $query->getYear(),
            ]
        )->fetchOne();

        return new FindMovingTimePerSportTypeResponse(
            movingTimePerSportType: $this->connection->executeQuery(
                <<<SQL
                SELECT sportType, SUM(movingTimeInSeconds) as movingTimeInSeconds
                FROM Activity
                WHERE strftime('%Y',startDateTime) = :year
                GROUP BY sportType
                ORDER BY sportType ASC
                SQL,
                [
                    'year' => (string) $query->getYear(),
                ]
            )->fetchAllKeyValue(),
            totalMovingTime: $totalMovingTime
        );
    }
}
