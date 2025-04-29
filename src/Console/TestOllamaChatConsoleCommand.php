<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\MyAgent;
use App\Domain\Integration\AI\Ollama\OllamaConfig;
use Inspector\Inspector;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\AgentMonitoring;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:ollama:test', description: 'Test Ollama')]
class TestOllamaChatConsoleCommand extends Command
{
    public function __construct(
        private readonly Inspector $inspector,
        private readonly OllamaConfig $config,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = MyAgent::make($this->config)
            ->observe(
                new AgentMonitoring($this->inspector)
            )
            ->chat(new UserMessage('Hi!'));

        $output->writeln($response->getContent());

        return Command::SUCCESS;
    }
}
