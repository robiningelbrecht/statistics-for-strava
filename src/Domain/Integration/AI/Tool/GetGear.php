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
            Retrieves the athlete’s gear information from the database, including name, type, and distance.
            Use this tool when the user asks about equipment such as shoes, bikes, or other gear. 
            It provides the details needed to track usage or summarize performance by equipment. 
            Example requests include “Show my bike details” or “How many kilometers have I ran with my shoes?”
            DESC
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        $gears = $this->gearRepository->findAll();

        return $gears->map(fn (Gear $gear): array => $gear->exportForAITooling());
    }
}
