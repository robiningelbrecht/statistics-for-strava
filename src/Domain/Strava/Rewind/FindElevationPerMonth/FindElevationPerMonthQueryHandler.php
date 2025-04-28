<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindElevationPerMonth;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class FindElevationPerMonthQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindElevationPerMonth);

        $totalElevation = (int) $this->connection->executeQuery(
            <<<SQL
                SELECT SUM(elevation) as distance
                FROM Activity
                WHERE strftime('%Y',startDateTime) = :year
            SQL,
            [
                'year' => (string) $query->getYear(),
            ]
        )->fetchOne();

        $results = $this->connection->executeQuery(
            <<<SQL
                SELECT strftime('%Y-%m', startDateTime) AS yearAndMonth, sportType, SUM(elevation) as elevation
                FROM Activity
                WHERE strftime('%Y',startDateTime) = :year
                GROUP BY sportType, yearAndMonth
                ORDER BY sportType ASC, yearAndMonth ASC
            SQL,
            [
                'year' => (string) $query->getYear(),
            ]
        )->fetchAllAssociative();

        return new FindElevationPerMonthResponse(
            elevationPerMonth: array_map(
                fn (array $result) => [
                    Month::fromDate(SerializableDateTime::fromString(sprintf('%s-01', $result['yearAndMonth']))),
                    SportType::from($result['sportType']),
                    Meter::from($result['elevation']),
                ],
                $results,
            ),
            totalElevation: Meter::from($totalElevation)
        );
    }
}
