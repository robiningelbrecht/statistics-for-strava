<?php

declare(strict_types=1);

namespace App\Domain\Segment\SegmentEffort;

use Doctrine\DBAL\Connection;

final class SegmentEffortRankingMap
{
    /** @var array<string, int> */
    private array $map = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array<string, int>
     */
    private function buildMap(): array
    {
        $query = 'SELECT segmentEffortId, ROW_NUMBER() OVER (
                    PARTITION BY segmentId
                    ORDER BY elapsedTimeInSeconds ASC
                ) rank
                FROM SegmentEffort
                ORDER BY segmentId';

        /** @var array<string, int> $results */
        $results = $this->connection->executeQuery($query)->fetchAllKeyValue();

        return $results;
    }

    public function getRankFor(SegmentEffortId $segmentEffortId): ?int
    {
        if (empty($this->map)) {
            $this->map = $this->buildMap();
        }

        return $this->map[(string) $segmentEffortId] ?? null;
    }
}
