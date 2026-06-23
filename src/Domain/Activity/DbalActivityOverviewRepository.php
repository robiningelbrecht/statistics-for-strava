<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Repository\Overview;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalActivityOverviewRepository extends DbalRepository implements ActivityOverviewRepository
{
    public function find(Pagination $pagination): Overview
    {
        $results = $this->connection->createQueryBuilder()
            ->select('a.activityId', 'a.name', 'a.sportType', 'a.startDateTime', 'a.distance')
            ->from('Activity', 'a')
            ->orderBy('a.startDateTime', 'DESC')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->executeQuery()
            ->fetchAllAssociative();

        $total = (int) $this->connection
            ->executeQuery('SELECT COUNT(*) FROM Activity')
            ->fetchOne();

        return Overview::create(
            pagination: $pagination,
            total: $total,
            items: array_map($this->hydrate(...), $results),
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ActivityOverviewItem
    {
        $startDate = SerializableDateTime::fromString($result['startDateTime']);
        $sportType = SportType::from($result['sportType']);

        return ActivityOverviewItem::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            name: '' !== trim((string) $result['name'])
                ? ActivityName::fromString($result['name'])
                : ActivityName::from($startDate, $sportType),
            sportType: $sportType,
            startDate: $startDate,
            distance: Meter::from($result['distance'])->toKilometer(),
        );
    }
}
