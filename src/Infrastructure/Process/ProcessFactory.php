<?php

namespace App\Infrastructure\Process;

use Symfony\Component\Process\Process;

interface ProcessFactory
{
    /**
     * @param string[] $command
     */
    public function create(array $command): Process;
}
