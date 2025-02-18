<?php

namespace App\Domain\App\BuildApp;

use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Component\Console\Output\OutputInterface;

final class BuildApp extends DomainCommand
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly SerializableDateTime $now,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getCurrentDateTime(): SerializableDateTime
    {
        return $this->now;
    }
}
