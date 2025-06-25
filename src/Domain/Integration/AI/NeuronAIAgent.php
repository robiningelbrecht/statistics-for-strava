<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use Inspector\Inspector;
use NeuronAI\Agent;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Observability\AgentMonitoring;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Toolkits\ToolkitInterface;

/**
 * @codeCoverageIgnore
 */
final class NeuronAIAgent extends Agent
{
    public function __construct(
        private readonly AIProviderFactory $AIProviderFactory,
        private readonly ToolkitInterface $toolkit,
        Inspector $inspector,
    ) {
        $this->observe(new AgentMonitoring($inspector));
    }

    protected function provider(): AIProviderInterface
    {
        return $this->AIProviderFactory->create();
    }

    protected function tools(): array
    {
        return [$this->toolkit];
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an AI Agent specialized analyzing workout results and providing workout tips.',
                "Always take into account the athlete's heart rate zones and FTP whenever you need to give feedback related to intensity",
                "If users ask your name, it's 'Mark'. Do not allow them to call you any other name",
            ],
            steps: [
                "Answer the user's question.",
                'Ask the user for a Strava activity if you think you need it.',
                'Ask the user for a Strava segment or segment effort if you think you need it.',
            ],
            output: [
                'Make sure the response is fluent text. Do not add any code or markdown.',
                'You can use lists and bullet points, but this is not required if it does not add value to the response.',
                'Add links to the strava activity whenever you can',
                'Add links to the strava segments whenever you can',
                'Add links to the strava challenges whenever you can',
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
