<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindActivityStartTimesPerHour;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\Connection;

final readonly class FindActivityStartTimesPerHourQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindActivityStartTimesPerHour);

        /** @var array<int, int> $results */
        $results = $this->connection->executeQuery(
            <<<SQL
                SELECT CAST(LTRIM(strftime('%H',startDateTime), '0') as INTEGER) as hour, COUNT(*) as count
                FROM Activity
                WHERE strftime('%Y',startDateTime) = :year
                GROUP BY hour
                ORDER BY hour ASC
            SQL,
            [
                'year' => (string) $query->getYear(),
            ]
        )->fetchAllKeyValue();

        return new FindActivityStartTimesPerHourResponse($results);
    }
}
