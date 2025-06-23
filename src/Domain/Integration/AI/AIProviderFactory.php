<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use App\Infrastructure\Config\AppConfig;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Deepseek;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\Mistral;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;

final readonly class AIProviderFactory
{
    public function __construct(
        private AppConfig $appConfig,
    ) {
    }

    public function create(): AIProviderInterface
    {
        // @TODO: CHECK IF FEATURE IS ENABLED.
        /** @var non-empty-string $providerName */
        $providerName = $this->appConfig->get('ai.provider');
        /** @var non-empty-array<string, mixed> $config */
        $config = $this->appConfig->get('ai.configuration');

        return match ($providerName) {
            'anthropic' => new Anthropic(
                key: $config['key'],
                model: $config['model'],
            ),
            'gemini' => new Gemini(
                key: $config['key'],
                model: $config['model'],
            ),
            'ollama' => new Ollama(
                url: $config['url'],
                model: $config['model'],
            ),
            'openAI' => new OpenAI(
                key: $config['key'],
                model: $config['model'],
            ),
            'deepseek' => new Deepseek(
                key: $config['key'],
                model: $config['model'],
            ),
            'mistral' => new Mistral(
                key: $config['key'],
                model: $config['model'],
            ),
            default => throw new \InvalidArgumentException(sprintf('AI provider "%s" is not supported', $providerName)),
        };
    }
}
