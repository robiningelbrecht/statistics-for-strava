<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearRepository;
use NeuronAI\Tools\Tool;

final class GetGear extends Tool
{
    public function __construct(
        private readonly GearRepository $gearRepository,
    ) {
        parent::__construct(
            'get_gear',
            <<<DESC
            Retrieves the athlete’s gear information from the database.
            Use this tool when the user refers to equipment such as shoes, bikes, or other gear
            Returns gear details like name, type and usage distance.
            DESC
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
