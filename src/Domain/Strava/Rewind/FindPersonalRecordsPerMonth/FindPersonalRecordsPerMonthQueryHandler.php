<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\ArrayParameterType;
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
                SELECT  CAST(strftime('%m', startDateTime) AS INTEGER) AS monthNumber,
                        SUM(JSON_EXTRACT(data, '$.pr_count')) as prCount
                FROM Activity
                WHERE strftime('%Y',startDateTime) IN (:years)
                GROUP BY monthNumber
                ORDER BY monthNumber DESC
            SQL,
            [
                'years' => array_map('strval', $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchAllAssociative();

        return new FindPersonalRecordsPerMonthResponse(array_map(
            fn (array $result) => [
                $result['monthNumber'],
                (int) $result['prCount'],
            ],
            $results
        ));
    }
}
