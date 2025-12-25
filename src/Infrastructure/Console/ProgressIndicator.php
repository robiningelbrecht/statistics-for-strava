<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressIndicator as SymfonyProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ProgressIndicator
{
    private SymfonyProgressIndicator $progressIndicator;

    public function __construct(OutputInterface $output)
    {
        $startTime = time();

        SymfonyProgressIndicator::setPlaceholderFormatterDefinition('indicator', static fn (): string => '');
        SymfonyProgressIndicator::setPlaceholderFormatterDefinition('elapsed', fn (): string => PlatformEnvironment::fromServer()->isTest() ? '3 s' : Helper::formatTime(time() - $startTime, 2));

        $this->progressIndicator = new SymfonyProgressIndicator(
            output: $output,
            format: 'verbose',
            finishedIndicatorValue: ''
        );
    }

    public function start(string $message): void
    {
        $this->progressIndicator->start($message);
    }

    public function updateMessage(string $message): void
    {
        $this->progressIndicator->setMessage($message);
    }

    public function finish(string $message): void
    {
        $this->progressIndicator->finish($message);
    }
}
