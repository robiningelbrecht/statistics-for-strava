<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Helper\ProgressIndicator as SymfonyProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

class ProgressIndicator
{
    private readonly SymfonyProgressIndicator $symfonyProgressIndicator;

    public function __construct(OutputInterface $output)
    {
        SymfonyProgressIndicator::setPlaceholderFormatterDefinition('indicator', static fn (): string => '');

        $this->symfonyProgressIndicator = new SymfonyProgressIndicator(
            output: $output,
            format: 'verbose',
            indicatorValues: ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇'],
            finishedIndicatorValue: ''
        );
    }

    public function start(string $message): void
    {
        $this->symfonyProgressIndicator->start($message);
    }

    public function updateMessage(string $message): void
    {
        $this->symfonyProgressIndicator->setMessage($message);
    }

    public function finish(string $message): void
    {
        $this->symfonyProgressIndicator->finish($message);
    }
}
