<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressIndicator as SymfonyProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

readonly class ProgressIndicator
{
    private SymfonyProgressIndicator $symfonyProgressIndicator;

    public function __construct(OutputInterface $output)
    {
        $startTime = time();

        SymfonyProgressIndicator::setPlaceholderFormatterDefinition('indicator', static fn (): string => '');
        SymfonyProgressIndicator::setPlaceholderFormatterDefinition('elapsed', fn (): string => PlatformEnvironment::fromServer()->isTest() ? '3 s' : Helper::formatTime(time() - $startTime, 2));

        $this->symfonyProgressIndicator = new SymfonyProgressIndicator(
            output: $output,
            format: 'verbose',
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
