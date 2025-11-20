<?php

namespace App\Tests\Console\Webook;

use App\Console\Webook\StravaViewWebhookSubscriptionConsoleCommand;
use App\Domain\Strava\Strava;
use App\Domain\Strava\Webhook\WebhookConfig;
use App\Tests\Console\ConsoleCommandTestCase;
use App\Tests\SpyOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Tester\CommandTester;

class StravaViewWebhookSubscriptionConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private StravaViewWebhookSubscriptionConsoleCommand $stravaViewWebhookSubscriptionConsoleCommand;
    private MockObject $logger;

    public function testExecute(): void
    {
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $command = $this->getCommandInApplication('app:strava:webhooks-view');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot(preg_replace('/\s+/', '', $commandTester->getDisplay()));
    }

    public function testExecuteWhenNoSubscriptions(): void
    {
        $strava = $this->createMock(Strava::class);
        $strava
            ->expects($this->once())
            ->method('getWebhookSubscription')
            ->willReturn([]);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $output = new SpyOutput();
        $command = new StravaViewWebhookSubscriptionConsoleCommand(
            $strava,
            WebhookConfig::fromArray(['enabled' => true, 'verifyToken' => 'le-token']),
            $this->logger,
        );
        $command->run($this->createMock(Input::class), $output);

        $this->assertMatchesTextSnapshot(preg_replace('/\s+/', '', (string) $output));
    }

    public function testExecuteWhenConfigDisabled(): void
    {
        $strava = $this->createMock(Strava::class);
        $strava
            ->expects($this->never())
            ->method('getWebhookSubscription');

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $output = new SpyOutput();
        $command = new StravaViewWebhookSubscriptionConsoleCommand(
            $strava,
            WebhookConfig::fromArray(['enabled' => false, 'verifyToken' => 'le-token']),
            $this->logger,
        );
        $command->run($this->createMock(Input::class), $output);

        $this->assertMatchesTextSnapshot(preg_replace('/\s+/', '', (string) $output));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->stravaViewWebhookSubscriptionConsoleCommand = new StravaViewWebhookSubscriptionConsoleCommand(
            $this->getContainer()->get(Strava::class),
            WebhookConfig::fromArray(['enabled' => true, 'verifyToken' => 'le-token']),
            $this->logger,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->stravaViewWebhookSubscriptionConsoleCommand;
    }
}
