<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class MakeStravaSegmentLink extends Tool
{
    public function __construct(
    ) {
        parent::__construct(
            'make_strava_segment_link',
            'Creates a link to the strava segment by a given activity id',
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
                name: 'segmentId',
                type: PropertyType::STRING,
                description: 'The id of the segment.',
                required: true
            ),
        ];
    }

    public function __invoke(string $segmentId): string
    {
        return sprintf('https://www.strava.com/segments/%s', $segmentId);
    }
}
