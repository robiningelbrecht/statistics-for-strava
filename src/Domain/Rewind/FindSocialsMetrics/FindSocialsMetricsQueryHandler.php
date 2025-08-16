<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindSocialsMetrics;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class FindSocialsMetricsQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindSocialsMetrics);

        /** @var array<string,mixed> $result */
        $result = $this->connection->executeQuery(
            <<<SQL
                SELECT SUM(kudoCount) as kudoCount, SUM(JSON_EXTRACT(data, '$.comment_count')) as commentCount
                FROM Activity
                WHERE strftime('%Y',startDateTime) IN (:years)
            SQL,
            [
                'years' => array_map('strval', $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchAssociative();

        return new FindSocialsMetricsResponse(
            kudoCount: (int) $result['kudoCount'],
            commentCount: (int) $result['commentCount'],
        );
    }
}
