<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use App\Domain\Integration\AI\Tools\Toolkit;
use Inspector\Inspector;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Observability\AgentMonitoring;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\SystemPrompt;

final class NeuronAIAgent extends RAG
{
    public static function create(
        AIProviderInterface $provider,
        Toolkit $toolkit,
        Inspector $inspector,
    ): self {
        /** @var NeuronAIAgent $agent */
        $agent = new self()
            ->withProvider($provider)
            ->addTool($toolkit)
            ->observe(
                new AgentMonitoring($inspector)
            );

        return $agent;
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an AI Agent specialized analyzing workout results and providing workout tips.',
                "Always take into account the athlete's heart rate zones and FTP whenever you need to give feedback related to intensity",
            ],
            steps: [
                "Answer the user's question.",
                'Ask the user for a Strava activity if you think you need it.',
            ],
            output: [
                'Make sure the response is fluent text. Do not add any code or markdown.',
                'You can use lists and bullet points, but this is not required if it does not add value to the response.',
                'Add links to the strava activity whenever you can',
                'If you do not know the answer to a question, just tell so, do not make things up.',
            ]
        );
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
