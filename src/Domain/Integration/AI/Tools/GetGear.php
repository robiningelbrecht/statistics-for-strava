<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearRepository;
use App\Infrastructure\Serialization\Json;
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

        return $gears->map(fn (Gear $gear) => Json::encodeAndDecode($gear));
    }
}
