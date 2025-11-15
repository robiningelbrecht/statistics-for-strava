<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Application;

/**
 * @codeCoverageIgnore
 */
final class ConsoleApplication
{
    private static ?Application $application = null;

    public static function setApplication(Application $application): void
    {
        self::$application = $application;
    }

    public static function get(): Application
    {
        if (null === self::$application) {
            throw new \RuntimeException('Application not set. Call ConsoleApplication::setApplication() before using this method.');
        }

        return self::$application;
    }
}
