<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\NeuronAiAgent;
use App\Domain\Integration\AI\Ollama\OllamaConfig;
use Inspector\Inspector;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\AgentMonitoring;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand(name: 'app:ollama:test', description: 'Test Ollama')]
class TestOllamaChatConsoleCommand extends Command
{
    public function __construct(
        private readonly Inspector $inspector,
        private readonly OllamaConfig $config,
        private readonly HubInterface $hub,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = NeuronAiAgent::make($this->config)
            ->observe(
                new AgentMonitoring($this->inspector)
            )
            ->chat(new UserMessage('Hi! What can you do?'));

        $update = new Update(
            'https://example.com/books/1',
            json_encode(['answer' => $response->getContent()])
        );

        $this->hub->publish($update);

        return Command::SUCCESS;
    }
}
