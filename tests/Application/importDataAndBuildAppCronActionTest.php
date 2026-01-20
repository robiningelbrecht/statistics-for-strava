<?php

namespace App\Tests\Application;

use App\Application\AppUrl;
use App\Application\importDataAndBuildAppCronAction;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Webhook\WebhookAspectType;
use App\Domain\Strava\Webhook\WebhookEvent;
use App\Domain\Strava\Webhook\WebhookEventRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleOutputSnapshotDriver;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use App\Tests\SpySymfonyStyleOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class importDataAndBuildAppCronActionTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private importDataAndBuildAppCronAction $importAndBuildAppCronAction;
    private CommandBus $commandBus;
    private MockObject $migrationRunner;

    public function testRun(): void
    {
        $output = new SpySymfonyStyleOutput(new StringInput('input'), new NullOutput());

        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $this->importAndBuildAppCronAction->run($output);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertMatchesSnapshot($output, new ConsoleOutputSnapshotDriver());
    }

    public function testRunForWebhooks(): void
    {
        $output = new SpySymfonyStyleOutput(new StringInput('input'), new NullOutput());

        $event = WebhookEvent::create(
            objectId: '1',
            objectType: 'activity',
            aspectType: WebhookAspectType::CREATE,
            payload: [],
        );
        $this->getContainer()->get(WebhookEventRepository::class)->add($event);

        $event = WebhookEvent::create(
            objectId: '2',
            objectType: 'activity',
            aspectType: WebhookAspectType::UPDATE,
            payload: [],
        );
        $this->getContainer()->get(WebhookEventRepository::class)->add($event);
        $event = WebhookEvent::create(
            objectId: '3',
            objectType: 'activity',
            aspectType: WebhookAspectType::DELETE,
            payload: [],
        );
        $this->getContainer()->get(WebhookEventRepository::class)->add($event);

        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $this->importAndBuildAppCronAction->runForWebhooks($output);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testRunForWebhooksWhenLockIsAlreadyAcquired(): void
    {
        $output = new SpySymfonyStyleOutput(new StringInput('input'), new NullOutput());

        $event = WebhookEvent::create(
            objectId: '1',
            objectType: 'activity',
            aspectType: WebhookAspectType::CREATE,
            payload: [],
        );
        $this->getContainer()->get(WebhookEventRepository::class)->add($event);

        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test", "heartbeat": 1764806400}']
        );

        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $this->importAndBuildAppCronAction->runForWebhooks($output);
        $this->assertEmpty($this->commandBus->getDispatchedCommands());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importAndBuildAppCronAction = new importDataAndBuildAppCronAction(
            $this->commandBus = new SpyCommandBus(),
            $this->getContainer()->get(WebhookEventRepository::class),
            $this->getContainer()->get(ActivityWithRawDataRepository::class),
            new FixedResourceUsage(),
            AppUrl::fromString('http://localhost'),
            new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            $this->migrationRunner = $this->createMock(MigrationRunner::class),
        );
    }
}
