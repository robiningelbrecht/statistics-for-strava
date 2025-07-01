<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeRepository;
use NeuronAI\Tools\Tool;

final class GetChallenges extends Tool
{
    public function __construct(
        private readonly ChallengeRepository $challengeRepository,
    ) {
        parent::__construct(
            'get_challenges',
            'Retrieves the challenge data from the database',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        $challenges = $this->challengeRepository->findAll();

        return $challenges->map(fn (Challenge $challenge) => $challenge->exportForAITooling());
    }
}
