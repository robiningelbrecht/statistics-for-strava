<?php

namespace App\Tests\Console;

use App\Console\StravaWebhookUnsubscribeConsoleCommand;
use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class StravaWebhookUnsubscribeConsoleCommandTest extends ConsoleCommandTestCase
{
    private StravaWebhookUnsubscribeConsoleCommand $stravaWebhookUnsubscribeConsoleCommand;
    private MockObject $webhookSubscriptionService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaWebhookUnsubscribeConsoleCommand = new StravaWebhookUnsubscribeConsoleCommand(
            $this->webhookSubscriptionService = $this->createMock(WebhookSubscriptionService::class),
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->stravaWebhookUnsubscribeConsoleCommand;
    }

    public function testExecuteAborted(): void
    {
        $command = $this->getCommandInApplication('app:strava:webhook:unsubscribe');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']); // User declines confirmation
        $commandTester->execute([
            'subscription-id' => '12345',
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Aborted', $commandTester->getDisplay());
    }

    public function testExecuteSuccess(): void
    {
        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('deleteSubscription')
            ->with(12345);

        $command = $this->getCommandInApplication('app:strava:webhook:unsubscribe');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']); // User confirms
        $commandTester->execute([
            'subscription-id' => '12345',
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Webhook subscription deleted successfully', $commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $this->webhookSubscriptionService
            ->expects($this->once())
            ->method('deleteSubscription')
            ->willThrowException(new WebhookSubscriptionException('Subscription not found'));

        $command = $this->getCommandInApplication('app:strava:webhook:unsubscribe');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']); // User confirms
        $commandTester->execute([
            'subscription-id' => '12345',
        ]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to delete webhook subscription', $commandTester->getDisplay());
        $this->assertStringContainsString('Subscription not found', $commandTester->getDisplay());
    }
}
