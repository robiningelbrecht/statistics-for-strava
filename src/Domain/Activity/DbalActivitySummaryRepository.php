<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalActivitySummaryRepository extends DbalRepository implements ActivitySummaryRepository
{
    public function find(ActivityId $activityId): ActivitySummary
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('name, startDateTime')
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
        }

        return ActivitySummary::create(
            activityId: $activityId,
            name: $result['name'],
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
        );
    }
}
