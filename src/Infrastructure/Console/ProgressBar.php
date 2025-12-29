<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Helper\ProgressBar as SymfonyProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ProgressBar
{
    private SymfonyProgressBar $progressBar;

    public function __construct(
        private OutputInterface $output,
        int $maxSteps,
    ) {
        SymfonyProgressBar::setPlaceholderFormatterDefinition(
            'message',
            function (SymfonyProgressBar $progressBar): string {
                if ($message = $progressBar->getMessage()) {
                    return sprintf(' - %s', $message);
                }

                return '';
            }
        );

        $this->progressBar = new SymfonyProgressBar($output, $maxSteps);
        $this->progressBar->setRedrawFrequency(1);
        $this->progressBar->setFormat('%percent:3s%% [%bar%] %current%/%max%%message%');
    }

    public function start(): void
    {
        $this->progressBar->setMessage('Initializing...');
        $this->progressBar->start();
    }

    public function advance(): void
    {
        usleep(100 * 1000);
        $this->progressBar->advance();
    }

    public function finish(): void
    {
        $this->progressBar->setMessage('');
        $this->progressBar->finish();
        $this->output->writeln('');
    }

    public function updateMessage(string $message): void
    {
        $this->progressBar->setMessage($message);
    }
}
