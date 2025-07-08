<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Split\ActivitySplit;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class GetActivitySplits extends Tool
{
    public function __construct(
        private readonly ActivitySplitRepository $activitySplitRepository,
        private readonly UnitSystem $unitSystem,
    ) {
        parent::__construct(
            'get_activity_splits',
            <<<DESC
            Retrieves detailed split information for a specific activity from the database.
            Use this tool when the user asks about splits data within an activity or when a user ask for all details of an activity.
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
        $splits = $this->activitySplitRepository->findBy(
            activityId: $activityId,
            unitSystem: $this->unitSystem
        );

        return $splits->map(static fn (ActivitySplit $split) => $split->exportForAITooling());
    }
}
