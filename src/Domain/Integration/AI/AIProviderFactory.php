<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Deepseek\Deepseek;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\HuggingFace\HuggingFace;
use NeuronAI\Providers\Mistral\Mistral;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\AzureOpenAI;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Providers\OpenAI\Responses\OpenAIResponses;
use NeuronAI\Providers\OpenAILike;
use NeuronAI\Providers\XAI\Grok;

final readonly class AIProviderFactory
{
    public function __construct(
        /** @var array<string, mixed> */
        #[\SensitiveParameter]
        private array $AIConfig,
    ) {
    }

    public function create(): AIProviderInterface
    {
        $providerName = $this->AIConfig['provider'] ?? throw new InvalidAIConfiguration('integrations.ai.provider', 'cannot be empty');
        /** @var non-empty-array<string, mixed> $config */
        $config = $this->AIConfig['configuration'] ?? throw new InvalidAIConfiguration('integrations.ai.configuration', 'cannot be empty');

        $requiredConfigKeys = match ($providerName) {
            'ollama' => ['model', 'url'],
            'azureOpenAI' => ['key', 'endpoint', 'model', 'version'],
            'openAILike' => ['baseUri', 'key', 'model'],
            default => ['model', 'key'],
        };

        foreach ($requiredConfigKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidAIConfiguration('integrations.ai.configuration.'.$key, 'cannot be empty');
            }
        }

        return match ($providerName) {
            'anthropic' => new Anthropic(
                key: $config['key'],
                model: $config['model'],
            ),
            'azureOpenAI' => new AzureOpenAI(
                key: $config['key'],
                endpoint: $config['endpoint'],
                model: $config['model'],
                version: $config['version'],
            ),
            'deepseek' => new Deepseek(
                key: $config['key'],
                model: $config['model'],
            ),
            'gemini' => new Gemini(
                key: $config['key'],
                model: $config['model'],
            ),
            'grok' => new Grok(
                key: $config['key'],
                model: $config['model'],
            ),
            'huggingFace' => new HuggingFace(
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
            'openAILike' => new OpenAILike(
                baseUri: $config['baseUri'],
                key: $config['key'],
                model: $config['model'],
            ),
            'openAIResponses' => new OpenAIResponses(
                key: $config['key'],
                model: $config['model'],
            ),
            'mistral' => new Mistral(
                key: $config['key'],
                model: $config['model'],
            ),
            default => throw new InvalidAIConfiguration(key: 'integrations.ai.provider', message: sprintf('AI provider "%s" is not supported', $providerName)),
        };
    }
}
