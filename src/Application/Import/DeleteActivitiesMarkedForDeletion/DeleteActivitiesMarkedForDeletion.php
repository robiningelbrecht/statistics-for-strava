<?php

declare(strict_types=1);

namespace App\Application\Import\DeleteActivitiesMarkedForDeletion;

use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class DeleteActivitiesMarkedForDeletion extends DomainCommand
{
    public function __construct(
        private OutputInterface $output,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
