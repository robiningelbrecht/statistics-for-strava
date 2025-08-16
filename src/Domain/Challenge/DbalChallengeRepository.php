<?php

namespace App\Domain\Challenge;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalChallengeRepository extends DbalRepository implements ChallengeRepository
{
    public function add(Challenge $challenge): void
    {
        $sql = 'INSERT INTO Challenge (challengeId, createdOn, name, logoUrl, localLogoUrl, slug)
        VALUES (:challengeId, :createdOn, :name, :logoUrl, :localLogoUrl, :slug)';

        $this->connection->executeStatement($sql, [
            'challengeId' => (string) $challenge->getId(),
            'createdOn' => $challenge->getCreatedOn(),
            'name' => $challenge->getName(),
            'logoUrl' => $challenge->getLogoUrl(),
            'localLogoUrl' => $challenge->getLocalLogoUrl(),
            'slug' => $challenge->getSlug(),
        ]);
    }

    public function findAll(): Challenges
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Challenge')
            ->orderBy('createdOn', 'DESC');

        return Challenges::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function count(): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('COUNT(*)')
            ->from('Challenge');

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }

    public function find(ChallengeId $challengeId): Challenge
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Challenge')
            ->andWhere('challengeId = :challengeId')
            ->setParameter('challengeId', (string) $challengeId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Challenge "%s" not found', $challengeId));
        }

        return $this->hydrate($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): Challenge
    {
        return Challenge::fromState(
            challengeId: ChallengeId::fromString($result['challengeId']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            name: $result['name'],
            logoUrl: $result['logoUrl'],
            localLogoUrl: $result['localLogoUrl'],
            slug: $result['slug'],
        );
    }
}
