<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\NeuronAIAgent;
use GuzzleHttp\Exception\ClientException;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ai:agent-chat', description: 'Start a new AI agent chat')]
final class AIAgentChatConsoleCommand extends Command
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

        $io->success([
            "Hi 👋, I'm Mark, your personal workout assistant. Ask me anything about your workout history.",
            'Press CTRL+C and ENTER to exit',
        ]);

        while (true) {
            if ($this->forceExit) {
                break;
            }
            $question = new Question('<info><You></info> ');
            $userInput = $helper->ask($input, $output, $question);

            if (null === $userInput) {
                continue; // if the user just presses Enter
            }

            try {
                $response = $this->agent->chat(new UserMessage($userInput));
                $output->writeln('<comment><Mark></comment> '.$response->getContent());
            } catch (\Exception $e) {
                $message = $e->getMessage();
                if ($e instanceof ClientException) {
                    $message = $e->getResponse()?->getBody();
                }

                $output->writeln('<comment><Mark></comment> Oh no, I made a booboo...');
                $output->writeln(sprintf(
                    '<error>%s</error>',
                    $message
                ));
            }
        }

        return Command::SUCCESS;
    }
}
