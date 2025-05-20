<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use App\Domain\Integration\AI\Ollama\OllamaConfig;
use Inspector\Inspector;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Observability\AgentMonitoring;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\RAG;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class NeuronAiAgent extends RAG
{
    private function __construct(
        private readonly OllamaConfig $config,
    ) {
    }

    public static function create(
        OllamaConfig $config,
        Inspector $inspector,
    ): self {
        return new self($config)
            ->observe(
                new AgentMonitoring($inspector)
            );
    }

    protected function provider(): AIProviderInterface
    {
        return new Ollama(
            url: (string) $this->config->getUrl(),
            model: $this->config->getModel(),
        );
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You are an AI Agent specialized analyzing workout results and providing workout tips.'],
            steps: [
                'Retrieve the workout data from the database.',
                'Use the tools you have available to retrieve the database data.',
                'Write the summary.',
            ],
            output: [
                'Write a summary in a paragraph without using lists. Use just fluent text.',
                'After the summary add a list of three sentences as the three most important takeaways from your feedback.',
            ]
        );
    }

    protected function tools(): array
    {
        return [
            /*Tool::make(
                'get_user_workout',
                'Retrieve the user workout status from the database.',
            )->addProperty(
                new ToolProperty(
                    name: 'user_id',
                    type: 'integer',
                    description: 'The ID of the user.',
                    required: true
                )
            )->setCallable(fn () => call_user_func([$this, 'getUserWorkout'])),*/
        ];
    }

    protected function chatHistory(): AbstractChatHistory
    {
        return new FileChatHistory(
            directory: '/var/www/storage',
            key: 'agent',
            contextWindow: 50000
        );
    }
}
