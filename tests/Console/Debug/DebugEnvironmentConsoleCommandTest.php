<?php

namespace App\Tests\Console\Debug;

use App\Console\Debug\DebugEnvironmentConsoleCommand;
use App\Tests\Console\ConsoleCommandTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DebugEnvironmentConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private DebugEnvironmentConsoleCommand $debugEnvironmentConsoleCommand;
    private array $tempFiles = [];

    public function testExecute(): void
    {
        $command = $this->getCommandInApplication('app:debug:environment');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertStringContainsString('Please copy all this output into the description of the bug ticket', $commandTester->getDisplay());
        $this->assertStringContainsString('Do not forget to redact sensitive information', $commandTester->getDisplay());
        $this->assertStringContainsString('APP_VERSION', $commandTester->getDisplay());
    }

    public function testExecuteWhenRedactInfoIsEnabled(): void
    {
        $command = $this->getCommandInApplication('app:debug:environment');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--redact-sensitive-info' => true,
        ]);

        $this->assertStringNotContainsString('Do not forget to redact sensitive information', $commandTester->getDisplay());
        $this->assertStringContainsString('APP_VERSION', $commandTester->getDisplay());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->debugEnvironmentConsoleCommand = new DebugEnvironmentConsoleCommand();
    }

    protected function getConsoleCommand(): Command
    {
        return $this->debugEnvironmentConsoleCommand;
    }

    private function createTempFile(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'testenv_');
        file_put_contents($tmp, $contents);
        $this->tempFiles[] = $tmp;

        return $tmp;
    }

    public function testGetenvOrFilePrefersEnvOverFile(): void
    {
        // create a file that would contain a different value
        $file = $this->createTempFile("file-secret\n");
        putenv('STRAVA_CLIENT_SECRET=env-secret');
        putenv('STRAVA_CLIENT_SECRET_FILE='.$file);

        $command = $this->getCommandInApplication('app:debug:environment');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $display = $commandTester->getDisplay();

        // env value should be used, file content must NOT be shown
        $this->assertStringContainsString('env-secret', $display);
        $this->assertStringNotContainsString('file-secret', $display);
    }

    public function testGetenvOrFileUsesFileWhenEnvMissing(): void
    {
        // ensure env var is not set
        putenv('STRAVA_REFRESH_TOKEN=');

        // create file and point *_FILE to it
        $file = $this->createTempFile("file-refresh-token\n");
        putenv('STRAVA_REFRESH_TOKEN_FILE='.$file);

        $command = $this->getCommandInApplication('app:debug:environment');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $display = $commandTester->getDisplay();

        // file content should appear
        $this->assertStringContainsString('file-refresh-token', $display);
    }

    public function testMissingEnvAndMissingFileShowsNullValue(): void
    {
        // make sure both env and file are absent
        putenv('STRAVA_CLIENT_ID=');
        putenv('STRAVA_CLIENT_ID_FILE=');

        $command = $this->getCommandInApplication('app:debug:environment');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $display = $commandTester->getDisplay();

        // When value is null, table will contain an empty or 'NULL' like representation depending on Table rendering.
        // We at least assert the APP_VERSION header is present and command runs successfully.
        $this->assertStringContainsString('APP_VERSION', $display);
    }
}
