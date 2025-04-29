<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth;

use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class FindPersonalRecordsPerMonthQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindPersonalRecordsPerMonth);

        $results = $this->connection->executeQuery(
            <<<SQL
                SELECT  strftime('%Y-%m', startDateTime) AS yearAndMonth,
                        SUM(JSON_EXTRACT(data, '$.pr_count')) as prCount
                FROM Activity
                WHERE strftime('%Y',startDateTime) = :year
                GROUP BY yearAndMonth
                ORDER BY yearAndMonth DESC
            SQL,
            [
                'year' => (string) $query->getYear(),
            ]
        )->fetchAllAssociative();

        return new FindPersonalRecordsPerMonthResponse(array_map(
            fn (array $result) => [
                Month::fromDate(SerializableDateTime::fromString(sprintf('%s-01', $result['yearAndMonth']))),
                (int) $result['prCount'],
            ],
            $results
        ));
    }
}
