<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\NeuronAiAgent;
use NeuronAI\Chat\Messages\UserMessage;
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
        private readonly NeuronAiAgent $agent,
        private readonly HubInterface $hub,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->agent->chat(new UserMessage('Hi! What can you do?'));

        $update = new Update(
            'https://example.com/books/1',
            json_encode(['answer' => $response->getContent()])
        );

        $this->hub->publish($update);

        return Command::SUCCESS;
    }
}
