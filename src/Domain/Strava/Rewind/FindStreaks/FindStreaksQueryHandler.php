<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindStreaks;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class FindStreaksQueryHandler implements QueryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Query $query): Response
    {
        assert($query instanceof FindStreaks);

        /** @var int[] $daysOfTheYear */
        $daysOfTheYear = array_map('intval', $this->connection->executeQuery(
            <<<SQL
                SELECT LTRIM(strftime('%j',startDateTime), 0) as day
                FROM activity
                WHERE strftime('%Y',startDateTime) IN (:years)
                GROUP BY day
            SQL,
            [
                'years' => array_map('strval', $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchFirstColumn());

        /** @var int[] $weeksOfTheYear */
        $weeksOfTheYear = array_map('intval', $this->connection->executeQuery(
            <<<SQL
                SELECT LTRIM(strftime('%W',startDateTime), 0) as day
                FROM activity
                WHERE strftime('%Y',startDateTime) IN (:years)
                GROUP BY day
            SQL,
            [
                'years' => array_map('strval', $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchFirstColumn());

        /** @var int[] $monthsOfTheYear */
        $monthsOfTheYear = array_map('intval', $this->connection->executeQuery(
            <<<SQL
                SELECT LTRIM(strftime('%m',startDateTime), 0) as day
                FROM activity
                WHERE strftime('%Y',startDateTime) IN (:years)
                GROUP BY day
            SQL,
            [
                'years' => array_map('strval', $query->getYears()->toArray()),
            ],
            [
                'years' => ArrayParameterType::STRING,
            ]
        )->fetchFirstColumn());

        return new FindStreaksResponse(
            dayStreak: $this->findLongestStreakLength($daysOfTheYear),
            weekStreak: $this->findLongestStreakLength($weeksOfTheYear),
            monthStreak: $this->findLongestStreakLength($monthsOfTheYear),
        );
    }

    /**
     * @param int[] $numbers
     */
    private function findLongestStreakLength(array $numbers): int
    {
        if (empty($numbers)) {
            return 0;
        }

        sort($numbers);

        $longestStreak = $currentStreak = 1;

        for ($i = 1; $i < count($numbers); ++$i) {
            if ($numbers[$i - 1] + 1 === $numbers[$i]) {
                ++$currentStreak;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }

        return $longestStreak;
    }
}
