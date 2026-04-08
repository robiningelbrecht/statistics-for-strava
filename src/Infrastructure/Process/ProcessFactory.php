<?php

declare(strict_types=1);

namespace App\Infrastructure\Process;

use Symfony\Component\Process\Process;

interface ProcessFactory
{
    /**
     * @param string[] $command
     */
    public function create(array $command): Process;
}
