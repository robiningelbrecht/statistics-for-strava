<?php

namespace App\Tests\Domain\Integration\AI;

use App\Domain\Integration\AI\AIProviderFactory;
use App\Domain\Integration\AI\InvalidAIConfiguration;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AIProviderFactoryTest extends TestCase
{
    #[DataProvider(methodName: 'provideConfig')]
    public function testCreate(array $config, AIProviderInterface $expectedProvider): void
    {
        $this->assertEquals(
            $expectedProvider::class,
            new AIProviderFactory($config)->create()::class
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testCreateItShouldThrow(array $config, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidAIConfiguration::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        new AIProviderFactory($config)->create();
    }

    public static function provideConfig(): iterable
    {
        yield 'anthropic' => [
            [
                'provider' => 'anthropic',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new Anthropic('key', 'model'),
        ];

        yield 'azureOpenAI' => [
            [
                'provider' => 'azureOpenAI',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                    'endpoint' => 'endpoint',
                    'version' => 'version',
                ],
            ],
            new AzureOpenAI('key', 'endpoint', 'model', 'version'),
        ];

        yield 'deepseek' => [
            [
                'provider' => 'deepseek',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new Deepseek('key', 'model'),
        ];

        yield 'gemini' => [
            [
                'provider' => 'gemini',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new Gemini('key', 'model'),
        ];

        yield 'grok' => [
            [
                'provider' => 'grok',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new Grok('key', 'model'),
        ];

        yield 'huggingFace' => [
            [
                'provider' => 'huggingFace',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new HuggingFace('key', 'model'),
        ];

        yield 'ollama' => [
            [
                'provider' => 'ollama',
                'configuration' => [
                    'url' => 'url',
                    'model' => 'model',
                ],
            ],
            new Ollama('key', 'model'),
        ];

        yield 'openAI' => [
            [
                'provider' => 'openAI',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new OpenAI('key', 'model'),
        ];

        yield 'openAILike' => [
            [
                'provider' => 'openAILike',
                'configuration' => [
                    'baseUri' => 'baseUri',
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new OpenAILike('baseUri', 'key', 'model'),
        ];

        yield 'openAIResponses' => [
            [
                'provider' => 'openAIResponses',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new OpenAIResponses('key', 'model'),
        ];

        yield 'mistral' => [
            [
                'provider' => 'mistral',
                'configuration' => [
                    'key' => 'key',
                    'model' => 'model',
                ],
            ],
            new Mistral('key', 'model'),
        ];
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'Empty provider name' => [
            [],
            'integrations.ai.provider: cannot be empty',
        ];

        yield 'Empty configuration' => [
            [
                'provider' => 'anthropic',
            ],
            'integrations.ai.configuration: cannot be empty',
        ];

        yield 'Invalid configuration missing model' => [
            [
                'provider' => 'anthropic',
                'configuration' => [
                    'key' => 'lol',
                ],
            ],
            'integrations.ai.configuration.model: cannot be empty',
        ];

        yield 'Invalid configuration missing key' => [
            [
                'provider' => 'anthropic',
                'configuration' => [
                    'model' => 'lol',
                ],
            ],
            'integrations.ai.configuration.key: cannot be empty',
        ];

        yield 'Invalid configuration missing url' => [
            [
                'provider' => 'ollama',
                'configuration' => [
                    'model' => 'lol',
                ],
            ],
            'integrations.ai.configuration.url: cannot be empty',
        ];

        yield 'Invalid configuration missing baseUri' => [
            [
                'provider' => 'openAILike',
                'configuration' => [
                    'model' => 'lol',
                    'key' => 'key',
                ],
            ],
            'integrations.ai.configuration.baseUri: cannot be empty',
        ];

        yield 'Invalid configuration provider' => [
            [
                'provider' => 'lol',
                'configuration' => [
                    'model' => 'lol',
                    'key' => 'lol',
                ],
            ],
            'integrations.ai.provider: AI provider "lol" is not supported',
        ];
    }
}
