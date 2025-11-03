<?php

namespace App\Tests\Console;

use App\Console\StravaWebhookSubscribeConsoleCommand;
use App\Domain\Strava\Webhook\WebhookConfig;
use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class StravaWebhookSubscribeConsoleCommandTest extends ConsoleCommandTestCase
{
    private StravaWebhookSubscribeConsoleCommand $stravaWebhookSubscribeConsoleCommand;
    private MockObject $webhookConfig;
    private MockObject $webhookSubscriptionService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaWebhookSubscribeConsoleCommand = new StravaWebhookSubscribeConsoleCommand(
            $this->webhookConfig = $this->createMock(WebhookConfig::class),
            $this->webhookSubscriptionService = $this->createMock(WebhookSubscriptionService::class),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->stravaWebhookSubscribeConsoleCommand;
    }

    public function testExecuteWhenWebhooksDisabled(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(false);

        $command = $this->getCommandInApplication('app:strava:webhook:subscribe');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Webhooks are not enabled', $commandTester->getDisplay());
    }

    public function testExecuteWhenWebhooksNotConfigured(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $this->webhookConfig
            ->method('isConfigured')
            ->willReturn(false);

        $command = $this->getCommandInApplication('app:strava:webhook:subscribe');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('not properly configured', $commandTester->getDisplay());
    }

    public function testExecuteSuccess(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $this->webhookConfig
            ->method('isConfigured')
            ->willReturn(true);

        $this->webhookConfig
            ->method('getCallbackUrl')
            ->willReturn('https://example.com/webhook/strava');

        $this->webhookConfig
            ->method('getVerifyToken')
            ->willReturn('test-token');

        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('createSubscription')
            ->with('https://example.com/webhook/strava', 'test-token')
            ->willReturn(['id' => 12345]);

        $command = $this->getCommandInApplication('app:strava:webhook:subscribe');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Webhook subscription created successfully', $commandTester->getDisplay());
        $this->assertStringContainsString('12345', $commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $this->webhookConfig
            ->method('isEnabled')
            ->willReturn(true);

        $this->webhookConfig
            ->method('isConfigured')
            ->willReturn(true);

        $this->webhookConfig
            ->method('getCallbackUrl')
            ->willReturn('https://example.com/webhook/strava');

        $this->webhookConfig
            ->method('getVerifyToken')
            ->willReturn('test-token');

        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('createSubscription')
            ->willThrowException(new WebhookSubscriptionException('Callback URL not accessible'));

        $command = $this->getCommandInApplication('app:strava:webhook:subscribe');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to create webhook subscription', $commandTester->getDisplay());
        $this->assertStringContainsString('Callback URL not accessible', $commandTester->getDisplay());
    }
}
