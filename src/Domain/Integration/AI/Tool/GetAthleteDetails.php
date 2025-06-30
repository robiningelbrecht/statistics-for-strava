<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Athlete\AthleteRepository;
use NeuronAI\Tools\Tool;

final class GetAthleteDetails extends Tool
{
    public function __construct(
        private readonly AthleteRepository $athleteRepository,
    ) {
        parent::__construct(
            'get_athlete_details',
            'Retrieves athlete details from the database',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return $this->athleteRepository->find()->exportForAITooling();
    }
}
