<?php

namespace App\Tests\Console;

use App\Console\StravaWebhookViewConsoleCommand;
use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class StravaWebhookViewConsoleCommandTest extends ConsoleCommandTestCase
{
    private StravaWebhookViewConsoleCommand $stravaWebhookViewConsoleCommand;
    private MockObject $webhookSubscriptionService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaWebhookViewConsoleCommand = new StravaWebhookViewConsoleCommand(
            $this->webhookSubscriptionService = $this->createMock(WebhookSubscriptionService::class),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->stravaWebhookViewConsoleCommand;
    }

    public function testExecuteNoSubscriptions(): void
    {
        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('viewSubscription')
            ->willReturn([]);

        $command = $this->getCommandInApplication('app:strava:webhook:view');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('No webhook subscriptions found', $commandTester->getDisplay());
    }

    public function testExecuteWithSubscription(): void
    {
        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('viewSubscription')
            ->willReturn([[
                'id' => 12345,
                'application_id' => 67890,
                'callback_url' => 'https://example.com/webhook/strava',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
            ]]);

        $command = $this->getCommandInApplication('app:strava:webhook:view');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Webhook Subscription Found', $commandTester->getDisplay());
        $this->assertStringContainsString('12345', $commandTester->getDisplay());
        $this->assertStringContainsString('67890', $commandTester->getDisplay());
        $this->assertStringContainsString('https://example.com/webhook/strava', $commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('viewSubscription')
            ->willThrowException(new WebhookSubscriptionException('API error'));

        $command = $this->getCommandInApplication('app:strava:webhook:view');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to view webhook subscription', $commandTester->getDisplay());
        $this->assertStringContainsString('API error', $commandTester->getDisplay());
    }
}
