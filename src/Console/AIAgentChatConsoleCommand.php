<?php

declare(strict_types=1);

namespace App\Console;

use App\Infrastructure\Config\AppConfig;
use GuzzleHttp\Exception\ClientException;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'app:ai:agent-chat', description: 'Start a new AI agent chat')]
final class AIAgentChatConsoleCommand extends Command
{
    public function __construct(
        private readonly AppConfig $appConfig,
        private readonly AgentInterface $agent,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->appConfig->AIIntegrationIsEnabled()) {
            $io->error('The AI feature is not enabled.');

            return Command::SUCCESS;
        }

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $io->block(
            messages: [
                "Hi 👋, I'm Mark, your personal workout assistant.".PHP_EOL.
                'Feel free to ask me anything—whether it’s about your training history or tips to improve!',
                'Type "exit" to close the conversation.',
            ],
            style: 'fg=black;bg=green',
            padding: true
        );

        while (true) {
            $question = new Question('<info><You></info> ');
            $userInput = $helper->ask($input, $output, $question);

            if (null === $userInput) {
                continue; // if the user just presses Enter
            }

            if ('exit' === $userInput) {
                $output->writeln('<comment><Mark></comment> Mkey, bye 👋');
                break;
            }

            try {
                $stream = $this->agent->stream(new UserMessage($userInput));
                $first = true;
                foreach ($stream as $text) {
                    if ($first) {
                        $output->write('<comment><Mark></comment> ');
                        $first = false;
                    }
                    $output->write($text);
                }
                $output->writeln('');
            } catch (\Exception $e) {
                $message = $e->getMessage();
                if ($e instanceof ClientException) {
                    $message = $e->getResponse()->getBody()->getContents();
                }

                $output->writeln('');
                $output->writeln('<comment><Mark></comment> Oh no, I made a booboo...');
                $output->writeln(sprintf(
                    '<error>%s</error>',
                    $message
                ));

                break;
            }
        }

        return Command::SUCCESS;
    }
}
