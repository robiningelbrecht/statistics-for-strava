<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class GetActivitySegmentEfforts extends Tool
{
    public function __construct(
        private readonly SegmentEffortRepository $segmentEffortRepository,
    ) {
        parent::__construct(
            'get_activity_segments_efforts',
            <<<DESC
            Retrieves detailed segment information for a specific activity from the database.
            Use this tool when the user asks about segments and segment efforts within an activity 
            or when a user ask for all details of an activity.
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
        $segmentEfforts = $this->segmentEffortRepository->findByActivityId($activityId);

        return $segmentEfforts->map(
            fn (SegmentEffort $segmentEffort) => $segmentEffort->exportForAITooling()
        );
    }
}
