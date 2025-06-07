<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\NeuronAIAgent;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ollama:test', description: 'Test Ollama')]
final class TestOllamaChatConsoleCommand extends Command
{
    private bool $forceExit = false;

    public function __construct(
        private readonly NeuronAIAgent $agent,
    ) {
        parent::__construct();
    }

    /**
     * @return int[]
     */
    public function getSubscribedSignals(): array
    {
        return [
            SIGINT, // Ctrl+C
            SIGTERM, // Termination signal
        ];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->forceExit = true;

        return Command::SUCCESS;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->success('Welcome to the chat CLI. Press CTRL+C to exit.');

        while (true) {
            if ($this->forceExit) {
                break;
            }
            $question = new Question('<You> ');
            $userInput = $helper->ask($input, $output, $question);

            if (null === $userInput) {
                continue; // if the user just presses Enter
            }

            $output->write(' thinking... ');

            $response = $this->agent->chat(new UserMessage($userInput));
            $output->writeln('<Bot> :'.$response->getContent());
        }

        return Command::SUCCESS;
    }
}
