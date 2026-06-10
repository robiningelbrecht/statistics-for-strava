<?php

namespace App\Tests\Infrastructure\DependencyInjection;

use App\Infrastructure\DependencyInjection\SqliteDatabaseUrlEnvVarProcessor;
use PHPUnit\Framework\TestCase;

class SqliteDatabaseUrlEnvVarProcessorTest extends TestCase
{
    private string $directory;

    public function testGetEnvUsesNewDatabaseWhenNoneExists(): void
    {
        $processor = new SqliteDatabaseUrlEnvVarProcessor();

        $this->assertEquals(
            'sqlite:///'.$this->directory.'/dreeve.db?charset=utf8mb4',
            $processor->getEnv('sqlite_db_url', 'DIR', fn (): string => $this->directory),
        );
    }

    public function testGetEnvFallsBackToLegacyDatabaseWhenItExists(): void
    {
        touch($this->directory.'/strava.db');

        $processor = new SqliteDatabaseUrlEnvVarProcessor();

        $this->assertEquals(
            'sqlite:///'.$this->directory.'/strava.db?charset=utf8mb4',
            $processor->getEnv('sqlite_db_url', 'DIR', fn (): string => $this->directory),
        );
    }

    public function testGetEnvPrefersNewDatabaseWhenBothExist(): void
    {
        touch($this->directory.'/strava.db');
        touch($this->directory.'/dreeve.db');

        $processor = new SqliteDatabaseUrlEnvVarProcessor();

        $this->assertEquals(
            'sqlite:///'.$this->directory.'/dreeve.db?charset=utf8mb4',
            $processor->getEnv('sqlite_db_url', 'DIR', fn (): string => $this->directory),
        );
    }

    public function testGetEnvTrimsTrailingSlashFromDirectory(): void
    {
        $processor = new SqliteDatabaseUrlEnvVarProcessor();

        $this->assertEquals(
            'sqlite:///'.$this->directory.'/dreeve.db?charset=utf8mb4',
            $processor->getEnv('sqlite_db_url', 'DIR', fn (): string => $this->directory.'/'),
        );
    }

    public function testGetProvidedTypes(): void
    {
        $this->assertEquals(
            [
                'sqlite_db_url' => 'string',
            ],
            SqliteDatabaseUrlEnvVarProcessor::getProvidedTypes(),
        );
    }

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/'.uniqid('sqlite-db-url-', true);
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (['dreeve.db', 'strava.db'] as $file) {
            if (file_exists($this->directory.'/'.$file)) {
                unlink($this->directory.'/'.$file);
            }
        }
        rmdir($this->directory);
    }
}
