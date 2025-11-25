<?php

namespace App\Infrastructure\Daemon;

use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
final readonly class DefaultProcessFactory implements ProcessFactory
{
    public function createSymfonyProcess(array $command): Process
    {
        return new Process($command);
    }
}
