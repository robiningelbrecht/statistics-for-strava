<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

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
            'Retrieves detailed segment effort data from the database for a specific activity',
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
        $segmentEfforts = $this->segmentEffortRepository->findByActivityId($activityId);

        return $segmentEfforts->map(
            fn (SegmentEffort $segmentEffort) => $segmentEffort->exportForAITooling()
        );
    }
}
