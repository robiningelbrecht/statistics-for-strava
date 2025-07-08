<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class MakeStravaChallengeLink extends Tool
{
    public function __construct(
    ) {
        parent::__construct(
            'make_strava_challenge_link',
            <<<DESC
            Generates a direct Strava URL for a given challenge using its slug.
            Requires the challenge slug and returns a full link to the corresponding Strava challenge page.
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
                name: 'slug',
                type: PropertyType::STRING,
                description: 'The slug of the challenge.',
                required: true
            ),
        ];
    }

    public function __invoke(string $slug): string
    {
        return sprintf('https://www.strava.com/challenges/%s', $slug);
    }
}
