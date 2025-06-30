<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearRepository;
use NeuronAI\Tools\Tool;

final class GetGear extends Tool
{
    public function __construct(
        private readonly GearRepository $gearRepository,
    ) {
        parent::__construct(
            'get_gear',
            'Retrieves the gear data from the database',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        $gears = $this->gearRepository->findAll();

        return $gears->map(fn (Gear $gear) => $gear->exportForAITooling());
    }
}
