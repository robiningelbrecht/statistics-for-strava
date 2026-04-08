<?php

declare(strict_types=1);

namespace App\Domain\Challenge;

interface ChallengeRepository
{
    public function add(Challenge $challenge): void;

    public function findAll(): Challenges;

    public function count(): int;

    public function find(ChallengeId $challengeId): Challenge;

    public function updateChallengeId(ChallengeId $oldChallengeId, ChallengeId $newChallengeId): void;

    public function deleteWithNonAlphanumericIds(): void;
}
