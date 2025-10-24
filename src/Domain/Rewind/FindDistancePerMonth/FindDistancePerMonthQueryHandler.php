<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindDistancePerMonth;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class FindDistancePerMonthQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindDistancePerMonth);

        $totalDistance = (int) $this->connection->executeQuery(
            <<<SQL
                SELECT SUM(distance) as distance
                FROM Activity
                WHERE strftime('%Y',startDateTime) IN (:years)
            SQL,
            [
                'years' => array_map(strval(...), $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchOne();

        $results = $this->connection->executeQuery(
            <<<SQL
                SELECT CAST(strftime('%m', startDateTime) AS INTEGER) AS monthNumber, sportType, SUM(distance) as distance
                FROM Activity
                WHERE strftime('%Y',startDateTime) IN (:years)
                GROUP BY sportType, monthNumber
                ORDER BY sportType ASC, monthNumber ASC
            SQL,
            [
                'years' => array_map(strval(...), $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchAllAssociative();

        return new FindDistancePerMonthResponse(
            distancePerMonth: array_map(
                fn (array $result): array => [
                    $result['monthNumber'],
                    SportType::from($result['sportType']),
                    Meter::from($result['distance'])->toKilometer(),
                ],
                $results,
            ),
            totalDistance: Meter::from($totalDistance)->toKilometer()
        );
    }
}
