<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class GetActivityStreams extends Tool
{
    public function __construct(
        private readonly ActivityStreamRepository $activityStreamRepository,
    ) {
        parent::__construct(
            'get_activity_streams',
            <<<DESC
            Retrieves detailed stream information for a specific activity from the database.
            Use this tool when the user asks about stream data within an activity or when a user ask for all details of an activity.
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
        $streams = $this->activityStreamRepository->findByActivityId($activityId);

        return $streams->map(static fn (ActivityStream $stream) => $stream->exportForAITooling());
    }
}
