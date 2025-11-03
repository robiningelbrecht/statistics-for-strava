<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Output\OutputInterface;

trait ConsoleOutputAware
{
    private ?OutputInterface $output = null;

    public function setConsoleOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    protected function getConsoleOutput(): ?OutputInterface
    {
        return $this->output;
    }
}
