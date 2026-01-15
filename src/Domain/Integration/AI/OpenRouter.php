<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use NeuronAI\Providers\OpenAI\OpenAI;

final class OpenRouter extends OpenAI
{
    protected string $baseUri = 'https://openrouter.ai/api/v1';
}
