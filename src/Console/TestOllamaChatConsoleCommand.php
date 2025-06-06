<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\NeuronAIAgent;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:ollama:test', description: 'Test Ollama')]
class TestOllamaChatConsoleCommand extends Command
{
    public function __construct(
        private readonly NeuronAIAgent $agent,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->agent->chat(new UserMessage('Hi! What can you do?'));

        return Command::SUCCESS;
    }
}
