<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindAvailableRewindOptions;

use App\Domain\Strava\Rewind\RewindCutOffDate;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use Doctrine\DBAL\Connection;

final readonly class FindAvailableRewindOptionsQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindAvailableRewindOptions);

        $now = $query->getNow();
        $currentYear = $now->getYear();
        if (RewindCutOffDate::fromYear(Year::fromInt($currentYear))->isBefore($now)) {
            $currentYear = 0;
        }

        $years = $this->connection->executeQuery(
            'SELECT DISTINCT strftime("%Y",startDateTime) AS year FROM Activity
             WHERE year != :currentYear 
             ORDER BY year DESC',
            [
                'currentYear' => $currentYear,
            ]
        )->fetchFirstColumn();

        return new FindAvailableRewindOptionsResponse(Years::fromArray(array_map(
            static fn (int $year): Year => Year::fromInt((int) $year),
            $years
        )));
    }
}
