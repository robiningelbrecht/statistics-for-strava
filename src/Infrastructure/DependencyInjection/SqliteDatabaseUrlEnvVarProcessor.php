<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

final readonly class SqliteDatabaseUrlEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $directory = rtrim((string) $getEnv($name), '/');

        $current = $directory.'/dreeve.db';
        $legacy = $directory.'/strava.db';

        // Fall back to the legacy database when it exists and no new one has been
        // created yet, so existing installations keep using their data instead of
        // silently starting against a new, empty database.
        $path = (!file_exists($current) && file_exists($legacy)) ? $legacy : $current;

        return 'sqlite:///'.$path.'?charset=utf8mb4';
    }

    public static function getProvidedTypes(): array
    {
        return [
            'sqlite_db_url' => 'string',
        ];
    }
}
