<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class MakeStravaActivityLink extends Tool
{
    public function __construct(
    ) {
        parent::__construct(
            'make_strava_activity_link',
            <<<DESC
            Generates a direct Strava URL for a given activity ID.
            Requires an activity ID and returns a full link to the corresponding Strava activity page.
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

    public function __invoke(string $activityId): string
    {
        return sprintf('https://www.strava.com/activities/%s', $activityId);
    }
}
