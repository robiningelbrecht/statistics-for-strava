<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Challenge\Challenge;
use App\Domain\Challenge\ChallengeRepository;
use NeuronAI\Tools\Tool;

final class GetChallenges extends Tool
{
    public function __construct(
        private readonly ChallengeRepository $challengeRepository,
    ) {
        parent::__construct(
            'get_challenges',
            <<<DESC
            Retrieves all available challenge data from the database, including challenge name, start date, and completion date.
            Use this tool when the user asks about current, upcoming, or past challenges. 
            Example requests include “List the challenges I completed last month.”
            DESC
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        $challenges = $this->challengeRepository->findAll();

        return $challenges->map(fn (Challenge $challenge): array => $challenge->exportForAITooling());
    }
}
