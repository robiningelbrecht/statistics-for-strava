<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindActiveDays;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\Connection;

final readonly class FindActiveDaysQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindActiveDays);

        $numberOfActiveDays = (int) $this->connection->executeQuery(
            <<<SQL
                SELECT COUNT(*)
                FROM (
                SELECT strftime('%Y-%m-%d', startDateTime) AS date
                      FROM Activity
                      WHERE strftime('%Y', startDateTime) = :year
                      GROUP BY date
                  )
            SQL,
            [
                'year' => (string) $query->getYear(),
            ]
        )->fetchOne();

        return new FindActiveDaysResponse($numberOfActiveDays);
    }
}
