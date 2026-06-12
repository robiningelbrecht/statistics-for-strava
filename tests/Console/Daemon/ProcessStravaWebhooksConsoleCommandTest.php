<?php

namespace App\Tests\Console\Daemon;

use App\Application\AppUrl;
use App\Console\Daemon\ProcessStravaWebhooksConsoleCommand;
use App\Console\Daemon\RunStravaImportAndBuildAppConsoleCommand;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Import\ImportMode;
use App\Domain\Strava\Strava;
use App\Domain\Strava\Webhook\WebhookAspectType;
use App\Domain\Strava\Webhook\WebhookEvent;
use App\Domain\Strava\Webhook\WebhookEventRepository;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Doctrine\Migrations\VoidMigrationRunner;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Log\NullLogger;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
class ProcessStravaWebhooksConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private const string TODAY = '2025-12-04';

    private ProcessStravaWebhooksConsoleCommand $command;

    public function testProcessesWebhooksAndDelegatesToStravaImport(): void
    {
        foreach ([
            ['1', WebhookAspectType::CREATE],
            ['2', WebhookAspectType::UPDATE],
            ['3', WebhookAspectType::DELETE],
        ] as [$objectId, $aspectType]) {
            $this->getContainer()->get(WebhookEventRepository::class)->add(WebhookEvent::create(
                objectId: $objectId,
                objectType: 'activity',
                aspectType: $aspectType,
                payload: [],
            ));
        }

        $command = $this->getCommandInApplication('app:cron:process-webhooks');
        $command->getApplication()->addCommand($this->buildStravaImportCommand($spyCommandBus = new SpyCommandBus()));

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // The CREATE/UPDATE activity ids should be forwarded to the strava import, the DELETE id should not.
        $this->assertMatchesJsonSnapshot(Json::encode($spyCommandBus->getDispatchedCommands()));
    }

    public function testReturnsEarlyInFileMode(): void
    {
        $this->getContainer()->get(WebhookEventRepository::class)->add(WebhookEvent::create(
            objectId: '1',
            objectType: 'activity',
            aspectType: WebhookAspectType::CREATE,
            payload: [],
        ));

        $command = $this->buildWebhooksCommand(ImportMode::FILES);
        $commandTester = new CommandTester($this->addToApplication($command));
        $statusCode = $commandTester->execute(['command' => $command->getName()]);

        $this->assertSame(Command::SUCCESS, $statusCode);
    }

    public function testWhenThereAreNoWebhookEvents(): void
    {
        $command = $this->getCommandInApplication('app:cron:process-webhooks');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertStringContainsString('No webhook events left to process...', $commandTester->getDisplay());
    }

    public function testPostponesWhenLockIsAlreadyAcquired(): void
    {
        $this->getContainer()->get(WebhookEventRepository::class)->add(WebhookEvent::create(
            objectId: '1',
            objectType: 'activity',
            aspectType: WebhookAspectType::CREATE,
            payload: [],
        ));

        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test", "heartbeat": 1764806400}']
        );

        $command = $this->getCommandInApplication('app:cron:process-webhooks');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertStringContainsString(
            'Postponing Strava import, another process is importing data.',
            $commandTester->getDisplay(),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = $this->buildWebhooksCommand(ImportMode::STRAVA_API);
    }

    private function buildWebhooksCommand(ImportMode $importMode): ProcessStravaWebhooksConsoleCommand
    {
        return new ProcessStravaWebhooksConsoleCommand(
            webhookEventRepository: $this->getContainer()->get(WebhookEventRepository::class),
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            importMode: $importMode,
        );
    }

    private function buildStravaImportCommand(SpyCommandBus $commandBus): RunStravaImportAndBuildAppConsoleCommand
    {
        return new RunStravaImportAndBuildAppConsoleCommand(
            commandBus: $commandBus,
            resourceUsage: new FixedResourceUsage(),
            strava: $this->getContainer()->get(Strava::class),
            logger: new NullLogger(),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString(self::TODAY),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            migrationRunner: new VoidMigrationRunner(),
            fileSystemPermissionChecker: new SuccessfulPermissionChecker(),
            connection: $this->mockConnection(),
            appUrl: AppUrl::fromString('http://localhost'),
            importMode: ImportMode::STRAVA_API,
        );
    }

    private function mockConnection(): Connection
    {
        // The strava import only uses the connection to run "VACUUM", which cannot run inside the
        // transaction the test suite wraps each test in, so we stub it out.
        $connection = $this->createMock(Connection::class);
        $connection->method('executeStatement')->willReturn(0);

        return $connection;
    }

    private function addToApplication(Command $command): Command
    {
        $application = new Application();
        $application->addCommand($command);

        return $application->find($command->getName());
    }

    protected function getConsoleCommand(): Command
    {
        return $this->command;
    }
}
