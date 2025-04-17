<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class DbalRewindRepository extends DbalRepository implements RewindRepository
{
    public function getAvailableRewindYears(SerializableDateTime $now): Years
    {
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

        return Years::fromArray(array_map(
            static fn (int $year): Year => Year::fromInt((int) $year),
            $years
        ));
    }
}
