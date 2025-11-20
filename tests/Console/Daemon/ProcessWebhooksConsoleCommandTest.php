<?php

namespace App\Tests\Console\Daemon;

use App\BuildApp\AppUrl;
use App\BuildApp\importDataAndBuildAppCronAction;
use App\Console\Daemon\ProcessWebhooksConsoleCommand;
use App\Domain\Strava\Webhook\WebhookEvent;
use App\Domain\Strava\Webhook\WebhookEventRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessWebhooksConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;
    private ProcessWebhooksConsoleCommand $processWebhooksConsoleCommand;
    private CommandBus $commandBus;

    public function testExecute(): void
    {
        $event = WebhookEvent::create(
            objectId: '1',
            objectType: 'activity',
            payload: [],
        );

        $this->getContainer()->get(WebhookEventRepository::class)->add($event);

        $command = $this->getCommandInApplication('app:cron:process-webhooks');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot(str_replace(' ', '', $commandTester->getDisplay()));
        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testExecuteWhenNoWebhookEvents(): void
    {
        $command = $this->getCommandInApplication('app:cron:process-webhooks');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot(str_replace(' ', '', $commandTester->getDisplay()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processWebhooksConsoleCommand = new ProcessWebhooksConsoleCommand(
            $this->getContainer()->get(WebhookEventRepository::class),
            new importDataAndBuildAppCronAction(
                $this->commandBus = new SpyCommandBus(),
                new FixedResourceUsage(),
                AppUrl::fromString('http://localhost'),
            )
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->processWebhooksConsoleCommand;
    }
}
