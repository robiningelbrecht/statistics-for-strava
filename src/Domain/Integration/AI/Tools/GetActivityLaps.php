<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Lap\ActivityLap;
use App\Domain\Strava\Activity\Lap\ActivityLapRepository;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class GetActivityLaps extends Tool
{
    public function __construct(
        private readonly ActivityLapRepository $activityLapRepository,
    ) {
        parent::__construct(
            'get_activity_laps',
            'Retrieves detailed lap data from the database for a specific activity',
        );
    }

    /**
     * @return \NeuronAI\Tools\ToolPropertyInterface[]
     */
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'activityId',
                type: PropertyType::STRING,
                description: 'The id of the activity.',
                required: true
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $activityId): array
    {
        $activityId = ActivityId::fromUnprefixed($activityId);
        $laps = $this->activityLapRepository->findBy($activityId);

        return $laps->map(static fn (ActivityLap $lap) => $lap->exportForAITooling());
    }
}
