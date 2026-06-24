<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Repository\Overview;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalActivityOverviewRepository extends DbalRepository implements ActivityOverviewRepository
{
    public function find(Pagination $pagination): Overview
    {
        $results = $this->connection->createQueryBuilder()
            ->select(
                'a.activityId',
                'a.name',
                'a.sportType',
                'a.startDateTime',
                'a.deviceName',
                'a.isCommute',
                'a.totalImageCount',
                'g.name AS gearName',
            )
            ->from('Activity', 'a')
            ->leftJoin('a', 'Gear', 'g', 'a.gearId = g.gearId')
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
            gearName: $result['gearName'] ?? null,
            deviceName: $result['deviceName'] ?? null,
            isCommute: (bool) ($result['isCommute'] ?? false),
            totalImageCount: (int) ($result['totalImageCount'] ?? 0),
        );
    }
}
