<?php

declare(strict_types=1);

namespace App\Domain\Activity\SportType;

use App\Infrastructure\Repository\DbalRepository;
use Doctrine\DBAL\Connection;

final readonly class DbalSportTypeRepository extends DbalRepository implements SportTypeRepository
{
    public function __construct(
        Connection $connection,
        private SportTypesSortingOrder $sportTypesSortingOrder,
    ) {
        parent::__construct($connection);
    }

    public function findAll(): SportTypes
    {
        $orderByStatement = [];
        foreach ($this->sportTypesSortingOrder as $index => $sportType) {
            $orderByStatement[] = sprintf('WHEN "%s" THEN %d', $sportType->value, $index);
        }
        $orderByStatement[] = 'ELSE 9999';

        return SportTypes::fromArray(array_map(
            fn (string $sportType) => SportType::from($sportType),
            $this->connection->executeQuery(
                sprintf('SELECT DISTINCT sportType FROM Activity ORDER BY CASE sportType %s END', implode(' ', $orderByStatement))
            )->fetchFirstColumn()
        ));
    }
}
