<?php

namespace App\Infrastructure\Daemon;

use Symfony\Component\Process\Process;

interface ProcessFactory
{
    /**
     * @param string[] $command
     */
    public function createSymfonyProcess(array $command): Process;
}
