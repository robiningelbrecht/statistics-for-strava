<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar\FindMonthlyStats;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class FindMonthlyStatsQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindMonthlyStats);

        $results = $this->connection->executeQuery(
            <<<SQL
                SELECT strftime('%Y-%m', startDateTime) AS yearAndMonth, 
                       sportType,
                       COUNT(*) AS numberOfActivities,
                       SUM(distance) AS totalDistance,
                       SUM(elevation) AS totalElevation,
                       SUM(movingTimeInSeconds) AS totalMovingTime,
                       SUM(calories) as totalCalories
                FROM Activity
                GROUP BY yearAndMonth, sportType
                ORDER BY yearAndMonth DESC
            SQL,
        )->fetchAllAssociative();

        $response = [];
        foreach ($results as $result) {
            $response[] = [
                Month::fromDate(SerializableDateTime::fromString(sprintf('%s-01', $result['yearAndMonth']))),
                SportType::from($result['sportType']),
                $result['numberOfActivities'],
                Meter::from($result['totalDistance'])->toKilometer(),
                Meter::from($result['totalElevation']),
                Seconds::from($result['totalMovingTime']),
                $result['totalCalories'],
            ];
        }

        return new FindMonthlyStatsResponse($response);
    }
}
