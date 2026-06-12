<?php

namespace App\Tests\Infrastructure\Daemon\Cron;

use App\Domain\Import\ImportMode;
use App\Infrastructure\Daemon\Cron\CronAction;
use Cron\CronExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CronActionTest extends TestCase
{
    public function testGetIdAndExpression(): void
    {
        $cronAction = CronAction::create(
            id: 'importDataAndBuildApp',
            expression: new CronExpression('0 2 * * *'),
        );

        $this->assertSame('importDataAndBuildApp', $cronAction->getId());
        $this->assertEquals(new CronExpression('0 2 * * *'), $cronAction->getExpression());
    }

    #[DataProvider('provideCommands')]
    public function testGetCommand(string $id, string $expectedCommand): void
    {
        $cronAction = CronAction::create(
            id: $id,
            expression: new CronExpression('* * * * *'),
        );

        $this->assertSame($expectedCommand, $cronAction->getCommand());
    }

    public static function provideCommands(): iterable
    {
        yield 'importDataAndBuildApp' => ['importDataAndBuildApp', 'bin/console app:cron:run-strava-import'];
        yield 'gearMaintenanceNotification' => ['gearMaintenanceNotification', 'bin/console app:cron:gear-maintenance-notification'];
        yield 'appUpdateAvailableNotification' => ['appUpdateAvailableNotification', 'bin/console app:cron:app-update-available-notification'];
    }

    public function testGetCommandItShouldThrowOnUnknownAction(): void
    {
        $cronAction = CronAction::create(
            id: 'unknownAction',
            expression: new CronExpression('* * * * *'),
        );

        $this->expectExceptionObject(new \RuntimeException('Unsupported Cron action: unknownAction'));
        $cronAction->getCommand();
    }

    #[DataProvider('provideImportModeSupport')]
    public function testSupportsImportMode(string $id, ImportMode $importMode, bool $expected): void
    {
        $cronAction = CronAction::create(
            id: $id,
            expression: new CronExpression('* * * * *'),
        );

        $this->assertSame($expected, $cronAction->supportsImportMode($importMode));
    }

    public static function provideImportModeSupport(): iterable
    {
        yield 'strava import is not supported in file mode' => ['importDataAndBuildApp', ImportMode::FILES, false];
        yield 'strava import is supported in strava api mode' => ['importDataAndBuildApp', ImportMode::STRAVA_API, true];
        yield 'gear maintenance is supported in file mode' => ['gearMaintenanceNotification', ImportMode::FILES, true];
        yield 'gear maintenance is supported in strava api mode' => ['gearMaintenanceNotification', ImportMode::STRAVA_API, true];
        yield 'app update is supported in file mode' => ['appUpdateAvailableNotification', ImportMode::FILES, true];
        yield 'app update is supported in strava api mode' => ['appUpdateAvailableNotification', ImportMode::STRAVA_API, true];
    }
}
