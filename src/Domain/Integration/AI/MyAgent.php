<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use App\Domain\Integration\AI\Ollama\OllamaConfig;
use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\RAG;
use NeuronAI\SystemPrompt;

final class MyAgent extends RAG
{
    public function __construct(
        private OllamaConfig $config,
    ) {
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
            background: ['You are an AI Agent specialized in writing YouTube video summaries.'],
            steps: [
                'Get the url of a YouTube video, or ask the user to provide one.',
                'Use the tools you have available to retrieve the transcription of the video.',
                'Write the summary.',
            ],
            output: [
                'Write a summary in a paragraph without using lists. Use just fluent text.',
                'After the summary add a list of three sentences as the three most important takeaways from the video.',
            ]
        );
    }

    /*protected function chatHistory(): AbstractChatHistory
    {
        return new FileChatHistory(
            directory: '/home/app/storage/neuron',
            key: '[user-id]',
            contextWindow: 50000
        );
    }*/
}
