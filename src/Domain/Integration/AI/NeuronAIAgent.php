<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use Inspector\Inspector;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Observability\AgentMonitoring;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class NeuronAIAgent extends RAG
{
    public static function create(
        AIProviderInterface $provider,
        Inspector $inspector,
    ): self {
        /** @var NeuronAIAgent $agent */
        $agent = new self()
            ->withProvider($provider)
            ->observe(
                new AgentMonitoring($inspector)
            );

        return $agent;
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You are an AI Agent specialized analyzing workout results and providing workout tips.'],
            steps: [
                "Answer the user's question.",
            ],
            output: [
                'Write a summary in a paragraph without using lists. Use just fluent text.',
                'Do not add any markdown',
                'Make sure the response is fluent text'
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
