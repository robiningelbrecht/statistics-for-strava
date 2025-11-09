<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Application;

trait ConsoleApplicationAware
{
    private ?Application $application = null;

    public function setConsoleApplication(Application $application): void
    {
        $this->application = $application;
    }

    public function getConsoleApplication(): Application
    {
        if (is_null($this->application)) {
            throw new \RuntimeException('Call setConsoleApplication() before getConsoleApplication()');
        }

        return $this->application;
    }
}
