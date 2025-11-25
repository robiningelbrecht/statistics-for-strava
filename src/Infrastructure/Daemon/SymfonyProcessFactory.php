<?php

namespace App\Infrastructure\Daemon;

use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
final readonly class SymfonyProcessFactory implements ProcessFactory
{
    public function create(array $command): Process
    {
        return new Process($command);
    }
}
