<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindActiveAndRestDays;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class FindActiveAndRestDaysQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
        private Clock $clock,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindActiveAndRestDays);

        $numberOfActiveDays = (int) $this->connection->executeQuery(
            <<<SQL
                SELECT COUNT(*)
                FROM (
                    SELECT strftime('%Y-%m-%d', startDateTime) AS date
                          FROM Activity
                          WHERE strftime('%Y', startDateTime) IN (:years)
                          GROUP BY date
                )
            SQL,
            [
                'years' => array_map(strval(...), $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchOne();

        /** @var \App\Infrastructure\ValueObject\Time\Year $firstYear */
        $firstYear = $query->getYears()->getFirst();
        $today = $this->clock->getCurrentDateTimeImmutable()->format('Y-m-d');
        $startDate = SerializableDateTime::fromString(sprintf('%s-01-01 00:00:00', $firstYear->toInt()));
        $endDate = SerializableDateTime::fromString(sprintf('%s 23:59:59', $today));

        return new FindActiveAndRestDaysResponse(
            totalNumberOfDays: (int) $startDate->diff($endDate)->days,
            activeDays: $numberOfActiveDays,
        );
    }
}
