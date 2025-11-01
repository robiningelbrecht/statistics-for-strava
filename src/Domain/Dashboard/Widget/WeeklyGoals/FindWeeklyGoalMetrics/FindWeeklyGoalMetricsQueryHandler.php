<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeeklyGoals\FindWeeklyGoalMetrics;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class FindWeeklyGoalMetricsQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindWeeklyGoalMetrics);

        $sportTypes = $query->getSportTypes();

        $result = $this->connection->executeQuery(
            <<<SQL
                SELECT SUM(distance) AS totalDistance,
                       SUM(elevation) AS totalElevation,
                       SUM(movingTimeInSeconds) AS movingTime
                FROM Activity
                WHERE SportType IN(:sportTypes)
                AND strftime('%Y-%m-%d', startDateTime) BETWEEN :startDate AND :endDate
            SQL,
            [
                'sportTypes' => $sportTypes->map(fn (SportType $sportType) => $sportType->value),
                'startDate' => (string) $query->getWeek()->getFrom()->format('Y-m-d'),
                'endDate' => (string) $query->getWeek()->getTo()->format('Y-m-d'),
            ],
            [
                'sportTypes' => ArrayParameterType::STRING,
            ]
        )->fetchOne();

        return new FindWeeklyGoalMetricsResponse(
            distance: Meter::from($result['totalDistance'])->toKilometer(),
            elevation: Meter::from($result['totalElevation']),
            movingTime: Seconds::from($result['movingTime']),
        );
    }
}
