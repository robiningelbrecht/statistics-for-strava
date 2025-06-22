<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class MakeStravaActivityLink extends Tool
{
    public function __construct(
    ) {
        parent::__construct(
            'make_strava_link',
            'Creates a link to the strava activity by a given activity id',
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

    public function __invoke(string $activityId): string
    {
        return sprintf('https://www.strava.com/activities/%s', $activityId);
    }
}
