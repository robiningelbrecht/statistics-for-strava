<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Lap\ActivityLap;
use App\Domain\Activity\Lap\ActivityLapRepository;
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
            <<<DESC
            Retrieves detailed lap information for a specific activity from the database.
            Use this tool when the user asks about lap data within an activity or when a user ask for all details of an activity.
            Requires the activity ID as input.
            DESC
        );
    }

    /**
     * @return \NeuronAI\Tools\ToolPropertyInterface[]
     *
     * @codeCoverageIgnore
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
